<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Http\Requests\MarkTransactionPaidRequest;
use App\Modules\InvoiceVerification\Http\Requests\SchedulePaymentRequest;
use App\Modules\InvoiceVerification\Http\Requests\UploadPaymentProofRequest;
use App\Modules\InvoiceVerification\Services\FinalizationService;
use App\Modules\InvoiceVerification\Services\TransactionLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FinanceController extends Controller
{
    public function __construct(
        protected FinalizationService $finalizationService,
        protected TransactionLifecycleService $transactionLifecycleService,
    ) {
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()?->hasRole(RoleCode::FINANCE), 403);

        $sort = in_array($request->query('sort'), ['registration_number', 'vendor', 'status', 'scheduled_payment_at', 'payment_proof_file_name', 'created_at'], true)
            ? $request->query('sort')
            : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $proof = (string) $request->query('proof', '');

        $transactionsQuery = Transaction::query()
            ->select('transactions.*')
            ->with(['transactionType', 'vendor', 'owner.department', 'numberingRegister', 'compiledDocument'])
            ->whereIn('status', [TransactionStatus::RECEIVED, TransactionStatus::SCHEDULING_PAYMENT])
            ->when($search !== '', function ($query) use ($search) {
                $needle = '%'.mb_strtolower($search).'%';

                $query->where(function ($innerQuery) use ($needle) {
                    $innerQuery
                        ->whereRaw('LOWER(transactions.registration_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(transactions.title) LIKE ?', [$needle])
                        ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->whereRaw('LOWER(name) LIKE ?', [$needle]))
                        ->orWhereHas('owner', fn ($ownerQuery) => $ownerQuery->whereRaw('LOWER(name) LIKE ?', [$needle]));
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                if (in_array($status, [TransactionStatus::RECEIVED->value, TransactionStatus::SCHEDULING_PAYMENT->value], true)) {
                    $query->where('status', $status);
                }
            })
            ->when($proof === 'uploaded', fn ($query) => $query->whereNotNull('payment_proof_file_path'))
            ->when($proof === 'missing', fn ($query) => $query->whereNull('payment_proof_file_path'));

        if ($sort === 'vendor') {
            $transactionsQuery
                ->leftJoin('vendors', 'vendors.id', '=', 'transactions.vendor_id')
                ->leftJoin('users as owners', 'owners.id', '=', 'transactions.owner_user_id')
                ->orderByRaw('LOWER(COALESCE(vendors.name, owners.name, \'\')) '.$direction);
        } else {
            $transactionsQuery->orderBy('transactions.'.$sort, $direction);
        }

        $transactions = $transactionsQuery
            ->orderBy('transactions.registration_number')
            ->paginate(10)
            ->withQueryString();

        return view('invoice-verification.finance.index', compact('transactions', 'sort', 'direction', 'search', 'status', 'proof'));
    }

    public function schedule(SchedulePaymentRequest $request, Transaction $transaction)
    {
        if ($transaction->status !== TransactionStatus::RECEIVED) {
            throw ValidationException::withMessages(['scheduled_payment_at' => 'Scheduling pembayaran hanya dapat dilakukan dari status Received.']);
        }

        DB::transaction(function () use ($request, $transaction) {
            $transaction->forceFill([
                'scheduled_payment_at' => $request->validated('scheduled_payment_at'),
            ])->save();

            $this->transactionLifecycleService->schedulePayment($transaction, $request->user());
        });

        return redirect()
            ->route('invoice-verification.finance.index')
            ->with('success', 'Jadwal pembayaran berhasil disimpan.');
    }

    public function uploadProof(UploadPaymentProofRequest $request, Transaction $transaction)
    {
        if ($transaction->status !== TransactionStatus::SCHEDULING_PAYMENT) {
            throw ValidationException::withMessages(['payment_proof' => 'Bukti transfer hanya dapat diunggah saat Scheduling Payment.']);
        }

        $file = $request->file('payment_proof');
        $disk = config('invoice_verification.storage.documents_disk', 'public');
        $path = $file->store('transactions/'.$transaction->id.'/payments', $disk);

        try {
            DB::transaction(function () use ($transaction, $request, $file, $disk, $path) {
                $transaction->update([
                    'payment_proof_file_name' => $file->getClientOriginalName(),
                    'payment_proof_file_disk' => $disk,
                    'payment_proof_file_path' => $path,
                    'payment_proof_mime_type' => $file->getMimeType(),
                    'payment_proof_file_size' => $file->getSize(),
                    'payment_proof_uploaded_at' => now(),
                    'payment_proof_uploaded_by' => $request->user()->id,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }

        return redirect()
            ->route('invoice-verification.finance.index')
            ->with('success', 'Bukti transfer berhasil diunggah.');
    }

    public function previewProof(Transaction $transaction)
    {
        $this->authorize('view', $transaction);

        abort_unless($transaction->payment_proof_file_path && $transaction->payment_proof_file_disk, 404);
        abort_unless(Storage::disk($transaction->payment_proof_file_disk)->exists($transaction->payment_proof_file_path), 404);

        return Storage::disk($transaction->payment_proof_file_disk)->response(
            $transaction->payment_proof_file_path,
            $transaction->payment_proof_file_name ?? basename($transaction->payment_proof_file_path),
            ['Content-Type' => $transaction->payment_proof_mime_type ?: 'application/octet-stream'],
            'inline',
        );
    }

    public function markPaid(MarkTransactionPaidRequest $request, Transaction $transaction)
    {
        $transaction->loadMissing('invoiceMetadata');

        if ($transaction->status !== TransactionStatus::SCHEDULING_PAYMENT) {
            throw ValidationException::withMessages(['status' => 'Finance tidak dapat menandai transaksi non-Scheduling sebagai Paid.']);
        }

        if (! $transaction->scheduled_payment_at) {
            throw ValidationException::withMessages(['scheduled_payment_at' => 'Jadwal pembayaran wajib tersedia sebelum Paid.']);
        }

        $this->ensureInvoiceMetadataComplete($transaction);

        if (! $transaction->payment_proof_file_path) {
            throw ValidationException::withMessages(['payment_proof' => 'Bukti transfer wajib diunggah sebelum transaksi ditandai Paid.']);
        }

        DB::transaction(function () use ($request, $transaction) {
            $transaction->forceFill(['paid_at' => $request->validated('paid_at')])->save();
            $this->transactionLifecycleService->markPaid($transaction, $request->user());
        });

        return redirect()
            ->route('invoice-verification.finance.index')
            ->with('success', 'Transaksi berhasil ditandai Paid.');
    }

    private function ensureInvoiceMetadataComplete(Transaction $transaction): void
    {
        $metadata = $transaction->invoiceMetadata;
        $missing = [];

        if (! $metadata) {
            $missing = ['Nomor Invoice', 'Tanggal Invoice', 'Bank', 'Nomor Rekening', 'Atas Nama Rekening', 'Nilai Invoice'];
        } else {
            $requiredFields = [
                'invoice_number' => 'Nomor Invoice',
                'invoice_date' => 'Tanggal Invoice',
                'bank_name' => 'Bank',
                'account_number' => 'Nomor Rekening',
                'account_name' => 'Atas Nama Rekening',
            ];

            foreach ($requiredFields as $field => $label) {
                if (blank($metadata->{$field})) {
                    $missing[] = $label;
                }
            }

            if ($metadata->invoice_value === null || $metadata->invoice_value === '') {
                $missing[] = 'Nilai Invoice';
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'invoice_metadata' => 'Metadata invoice wajib dilengkapi sebelum Paid: '.implode(', ', $missing).'.',
            ]);
        }
    }
}
