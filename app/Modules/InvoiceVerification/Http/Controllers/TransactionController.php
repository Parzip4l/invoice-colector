<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Actions\CreateTransactionAction;
use App\Modules\InvoiceVerification\Actions\UploadTransactionDocumentAction;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceType;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\AuditLog;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\InvoiceMetadata;
use App\Modules\InvoiceVerification\Domain\Models\MemoRequest;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use App\Modules\InvoiceVerification\Http\Requests\StoreTransactionRequest;
use App\Modules\InvoiceVerification\Http\Requests\UpdateInvoiceMetadataRequest;
use App\Modules\InvoiceVerification\Services\AuditLogService;
use App\Modules\InvoiceVerification\Services\Contracts\EprocDataProviderInterface;
use App\Modules\InvoiceVerification\Services\PpaVerificationSheetService;
use App\Modules\InvoiceVerification\Services\TransactionLifecycleService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        protected CreateTransactionAction $createTransactionAction,
        protected UploadTransactionDocumentAction $uploadTransactionDocumentAction,
        protected AuditLogService $auditLogService,
        protected PpaVerificationSheetService $ppaVerificationSheetService,
        protected TransactionLifecycleService $transactionLifecycleService,
        protected EprocDataProviderInterface $eprocDataProvider,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Transaction::class);

        $user = $request->user();
        $sort = in_array($request->query('sort'), ['registration_number', 'transaction_type', 'vendor', 'status', 'current_step', 'created_at'], true)
            ? $request->query('sort')
            : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $transactionTypeId = (string) $request->query('transaction_type_id', '');

        $transactionsQuery = Transaction::query()
            ->select('transactions.*')
            ->with(['transactionType', 'vendor', 'division', 'department', 'invoiceMetadata'])
            ->when($user?->hasRole(RoleCode::VENDOR), function ($query) use ($user) {
                $vendorId = $user?->linkedVendor()?->id;

                $query->when($vendorId, fn ($vendorQuery) => $vendorQuery->where('vendor_id', $vendorId))
                    ->when(! $vendorId, fn ($vendorQuery) => $vendorQuery->whereRaw('1 = 0'));
            })
            ->when($user?->hasRole(RoleCode::USER_DIVISI), fn ($query) => $query->where('owner_user_id', $user->id))
            ->when($status !== '', fn ($query) => $query->where('transactions.status', $status))
            ->when($transactionTypeId !== '', fn ($query) => $query->where('transactions.transaction_type_id', $transactionTypeId))
            ->when($search !== '', function ($query) use ($search) {
                $needle = '%'.mb_strtolower($search).'%';

                $query->where(function ($innerQuery) use ($needle) {
                    $innerQuery->whereRaw('LOWER(transactions.registration_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(transactions.title) LIKE ?', [$needle])
                        ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->whereRaw('LOWER(name) LIKE ?', [$needle]))
                        ->orWhereHas('owner', fn ($ownerQuery) => $ownerQuery->whereRaw('LOWER(name) LIKE ?', [$needle]));
                });
            });

        if ($sort === 'vendor') {
            $transactionsQuery
                ->leftJoin('vendors', 'vendors.id', '=', 'transactions.vendor_id')
                ->leftJoin('users as owners', 'owners.id', '=', 'transactions.owner_user_id')
                ->orderByRaw('LOWER(COALESCE(vendors.name, owners.name, \'\')) '.$direction);
        } elseif ($sort === 'transaction_type') {
            $transactionsQuery
                ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
                ->orderBy('transaction_types.code', $direction);
        } else {
            $transactionsQuery->orderBy('transactions.'.$sort, $direction);
        }

        $transactions = $transactionsQuery
            ->orderBy('transactions.registration_number')
            ->paginate(10)
            ->withQueryString();

        $transactionTypes = TransactionType::query()->orderBy('name')->get();
        $statusOptions = TransactionStatus::workflowCases();

        return view('invoice-verification.transactions.index', compact('transactions', 'transactionTypes', 'statusOptions', 'sort', 'direction', 'search', 'status', 'transactionTypeId'));
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

        $transactionTypes = TransactionType::query()
            ->orderBy('name')
            ->get()
            ->filter(function (TransactionType $type) use ($user) {
                if ($user?->hasRole(RoleCode::VENDOR)) {
                    return $type->code?->value === 'PPA';
                }

                if ($user?->hasRole(RoleCode::USER_DIVISI)) {
                    return $type->code?->isInternalVendorType();
                }

                return false;
            });

        $spuTransactions = Transaction::query()
            ->with('transactionType')
            ->where('owner_user_id', $user?->id)
            ->whereHas('transactionType', fn ($query) => $query->where('code', 'SPU'))
            ->latest()
            ->get();

        $ppaDocumentTypes = DocumentType::query()
            ->whereHas('transactionType', fn ($query) => $query->where('code', 'PPA'))
            ->where('source_type', DocumentSourceType::VENDOR->value)
            ->orderBy('sort_order')
            ->get();

        return view('invoice-verification.transactions.create', [
            'transactionTypes' => $transactionTypes,
            'vendors' => $user?->hasRole(RoleCode::VENDOR) && $user?->linkedVendor() ? collect([$user->linkedVendor()]) : Vendor::query()->orderBy('name')->get(),
            'linkedVendor' => $user?->linkedVendor(),
            'currentDivision' => $user?->division,
            'departments' => Department::query()
                ->where('division_id', $user?->division_id)
                ->orderBy('name')
                ->get(),
            'memoRequests' => $memoRequests,
            'agreementReferences' => $agreementReferences,
            'ppaDocumentTypes' => $ppaDocumentTypes,
            'spuTransactions' => $spuTransactions,
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

    public function submit(Request $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $transaction->loadMissing('transactionType', 'latestDocuments.documentType');
        $this->validateRequiredDocumentsForSubmit($transaction);

        $this->transactionLifecycleService->submitByVendor($transaction, $request->user());

        return redirect()
            ->route('invoice-verification.transactions.show', $transaction)
            ->with('success', 'Transaksi berhasil disubmit ke Accounting.');
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
            'creator',
            'owner',
            'parentSpuTransaction',
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
        $payload = $request->validated();

        $payload['vendor_id'] = $payload['vendor_id'] ?? $transaction->vendor_id;
        $payload['memo_number'] = $metadata?->memo_number ?? $transaction->memoRequest?->memo_number;
        $payload['contract_number'] = $payload['contract_number'] ?? $metadata?->contract_number ?? $transaction->agreementReference?->contract_number ?? $transaction->contract_number;
        $payload['contract_value'] = $payload['contract_value'] ?? $metadata?->contract_value ?? $transaction->agreementReference?->contract_value ?? $transaction->contract_value;
        $payload['description'] = $payload['description'] ?? $metadata?->description ?? $transaction->description;

        $metadata = InvoiceMetadata::updateOrCreate(
            ['transaction_id' => $transaction->id],
            $payload,
        );

        $this->auditLogService->log(
            module: 'invoice-metadata',
            action: 'update_transaction',
            actor: $request->user(),
            transaction: $transaction,
            referenceType: $metadata::class,
            referenceId: $metadata->id,
            oldValue: $oldValue,
            newValue: $metadata->fresh()?->toArray() ?? [],
        );

        return redirect()
            ->route('invoice-verification.transactions.show', $transaction)
            ->with('success', 'Metadata invoice berhasil diperbarui.');
    }

    private function validateRequiredDocumentsForSubmit(Transaction $transaction): void
    {
        $requiredTypes = DocumentType::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('is_required', true)
            ->where('source_type', 'VENDOR')
            ->get();

        foreach ($requiredTypes as $documentType) {
            $exists = $transaction->latestDocuments()
                ->where('document_type_id', $documentType->id)
                ->exists();

            if (! $exists) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'documents' => sprintf('Dokumen %s wajib diunggah sebelum submit.', $documentType->name),
                ]);
            }
        }
    }
}
