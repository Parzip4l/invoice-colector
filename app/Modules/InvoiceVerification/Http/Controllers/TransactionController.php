<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Actions\CreateTransactionAction;
use App\Modules\InvoiceVerification\Actions\UploadTransactionDocumentAction;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceType;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\AuditLog;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\MemoRequest;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use App\Modules\InvoiceVerification\Http\Requests\StoreTransactionRequest;
use App\Modules\InvoiceVerification\Http\Requests\UpdateInvoiceMetadataRequest;
use App\Modules\InvoiceVerification\Services\AuditLogService;
use App\Modules\InvoiceVerification\Services\PpaVerificationSheetService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        protected CreateTransactionAction $createTransactionAction,
        protected UploadTransactionDocumentAction $uploadTransactionDocumentAction,
        protected AuditLogService $auditLogService,
        protected PpaVerificationSheetService $ppaVerificationSheetService,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Transaction::class);

        $user = $request->user();

        $transactions = Transaction::query()
            ->with(['transactionType', 'vendor', 'division', 'department', 'invoiceMetadata'])
            ->when($user?->hasRole(RoleCode::VENDOR), function ($query) use ($user) {
                $vendorId = $user?->linkedVendor()?->id;

                $query->when($vendorId, fn ($vendorQuery) => $vendorQuery->where('vendor_id', $vendorId))
                    ->when(! $vendorId, fn ($vendorQuery) => $vendorQuery->whereRaw('1 = 0'));
            })
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('transaction_type_id')->toString(), fn ($query, $typeId) => $query->where('transaction_type_id', $typeId))
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('registration_number', 'like', '%'.$search.'%')
                        ->orWhere('title', 'like', '%'.$search.'%')
                        ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $transactionTypes = TransactionType::query()->orderBy('name')->get();

        return view('invoice-verification.transactions.index', compact('transactions', 'transactionTypes'));
    }

    public function create()
    {
        $this->authorize('create', Transaction::class);

        $user = auth()->user();

        $memoRequests = MemoRequest::query()
            ->when(! $user?->hasRole(RoleCode::AKUNTANSI), function ($query) use ($user) {
                $query->where('division_id', $user?->division_id);
            })
            ->orderBy('department_id')
            ->latest('memo_date')
            ->get();

        $agreementReferences = AgreementReference::query()
            ->when(! $user?->hasRole(RoleCode::AKUNTANSI), function ($query) use ($user) {
                $query->where('division_id', $user?->division_id);
            })
            ->orderBy('department_id')
            ->orderBy('contract_number')
            ->get();

        $ppaDocumentTypes = DocumentType::query()
            ->whereHas('transactionType', fn ($query) => $query->where('code', 'PPA'))
            ->where('source_type', DocumentSourceType::VENDOR->value)
            ->orderBy('sort_order')
            ->get();

        return view('invoice-verification.transactions.create', [
            'transactionTypes' => TransactionType::query()->orderBy('name')->get(),
            'vendors' => Vendor::query()->orderBy('name')->get(),
            'linkedVendor' => null,
            'currentDivision' => $user?->division,
            'departments' => Department::query()
                ->where('division_id', $user?->division_id)
                ->orderBy('name')
                ->get(),
            'memoRequests' => $memoRequests,
            'agreementReferences' => $agreementReferences,
            'ppaDocumentTypes' => $ppaDocumentTypes,
        ]);
    }

    public function store(StoreTransactionRequest $request)
    {
        $payload = $request->validated();

        $transaction = $this->createTransactionAction->execute($payload, $request->user());

        return redirect()
            ->route('invoice-verification.transactions.show', $transaction)
            ->with('success', 'Draft transaksi berhasil dibuat. Vendor dapat mengisi data tagihan dan upload dokumen dari Daftar Transaksi.');
    }

    public function show(Transaction $transaction)
    {
        $this->authorize('view', $transaction);

        $transaction->load([
            'transactionType',
            'vendor.defaultBank',
            'division',
            'department',
            'memoRequest',
            'agreementReference',
            'generatedDocuments.approvals.approvalFlow',
            'latestDocuments.documentType',
            'latestDocuments.vendorReview',
            'invoiceMetadata',
            'approvalTransactions.approvalFlow',
            'ppaVerificationSheet.items.documentType',
            'accountingVerification.items.transactionDocument.documentType',
            'numberingRegister',
            'compiledDocument.items',
            'statusHistory.changer',
        ]);

        $mismatches = $transaction->isPpa()
            ? $this->ppaVerificationSheetService->mismatchSummary($transaction)
            : [];

        $auditLogs = AuditLog::query()
            ->where('transaction_id', $transaction->id)
            ->latest('created_at')
            ->limit(20)
            ->get();

        return view('invoice-verification.transactions.show', compact('transaction', 'mismatches', 'auditLogs'));
    }

    public function updateInvoiceMetadata(UpdateInvoiceMetadataRequest $request, Transaction $transaction)
    {
        $metadata = $transaction->invoiceMetadata;
        $oldValue = $metadata?->toArray() ?? [];

        $metadata?->update($request->validated());

        $this->auditLogService->log(
            module: 'invoice-metadata',
            action: 'update_transaction',
            actor: $request->user(),
            transaction: $transaction,
            referenceType: get_class($metadata),
            referenceId: $metadata?->id,
            oldValue: $oldValue,
            newValue: $metadata?->fresh()?->toArray() ?? [],
        );

        return redirect()
            ->route('invoice-verification.transactions.show', $transaction)
            ->with('success', 'Metadata invoice berhasil diperbarui.');
    }
}
