<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentCode;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionDocumentStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Enums\VendorDocumentReviewStatus;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalFlow;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Domain\Models\GeneratedDocument;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\VendorDocumentReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ApprovalWorkflowService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected TransactionLifecycleService $transactionLifecycleService,
    ) {
    }

    public function bootstrapForGeneratedDocument(Transaction $transaction, GeneratedDocument $generatedDocument): Collection
    {
        $this->ensurePpaApproverFlows($transaction, $generatedDocument->document_code);

        $flows = ApprovalFlow::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('document_code', $generatedDocument->document_code)
            ->where('is_required', true)
            ->orderBy('step_no')
            ->get();

        return DB::transaction(function () use ($flows, $transaction, $generatedDocument) {
            return $flows->map(function (ApprovalFlow $flow) use ($transaction, $generatedDocument) {
                $approver = $this->resolveApprover($flow->step_code, $transaction);

                return ApprovalTransaction::create([
                    'transaction_id' => $transaction->id,
                    'generated_document_id' => $generatedDocument->id,
                    'approval_flow_id' => $flow->id,
                    'approver_user_id' => $approver->id,
                    'status' => ApprovalStatus::PENDING,
                ]);
            });
        });
    }

    public function bootstrapForTransactionReview(Transaction $transaction): Collection
    {
        $documentCode = 'TRANSACTION_REVIEW';
        $this->ensureStagedReviewFlows($transaction, $documentCode);

        $flows = ApprovalFlow::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('document_code', $documentCode)
            ->where('is_required', true)
            ->orderBy('step_no')
            ->get();

        return collect($flows)->map(function (ApprovalFlow $flow) use ($transaction) {
            $approver = $this->resolveApprover($flow->step_code, $transaction);

            return ApprovalTransaction::updateOrCreate(
                [
                    'transaction_id' => $transaction->id,
                    'generated_document_id' => null,
                    'approval_flow_id' => $flow->id,
                    'approver_user_id' => $approver->id,
                ],
                [
                    'status' => ApprovalStatus::PENDING,
                    'notes' => null,
                    'action_at' => null,
                ],
            );
        });
    }

    public function bootstrapForPpaVerificationSheet(Transaction $transaction): Collection
    {
        $this->ensurePpaApproverFlows($transaction, DocumentCode::PPA_LEMBAR_VERIFIKASI->value);

        $flows = ApprovalFlow::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('document_code', 'PPA_LEMBAR_VERIFIKASI')
            ->where('is_required', true)
            ->orderBy('step_no')
            ->get();

        return collect($flows)->map(function (ApprovalFlow $flow) use ($transaction) {
            $approver = $this->resolveApprover($flow->step_code, $transaction);

            return ApprovalTransaction::updateOrCreate(
                [
                    'transaction_id' => $transaction->id,
                    'generated_document_id' => null,
                    'approval_flow_id' => $flow->id,
                    'approver_user_id' => $approver->id,
                ],
                [
                    'status' => ApprovalStatus::PENDING,
                    'notes' => null,
                    'action_at' => null,
                ],
            );
        });
    }

    public function process(ApprovalTransaction $approvalTransaction, User $actor, ApprovalStatus $status, ?string $notes = null): ApprovalTransaction
    {
        if ($approvalTransaction->approver_user_id !== $actor->id) {
            throw ValidationException::withMessages([
                'approval' => 'Approval hanya dapat diproses oleh approver yang ditugaskan.',
            ]);
        }

        $approvalTransaction->forceFill([
            'status' => $status,
            'notes' => $notes,
            'action_at' => now(),
        ])->save();

        $transaction = $approvalTransaction->transaction()->with('approvalTransactions')->firstOrFail();

        if ($status === ApprovalStatus::REJECTED) {
            $this->markVendorDocumentsForRevision(
                $transaction,
                $actor,
                $notes ?: 'Approval ditolak. Vendor diminta menyesuaikan kembali dokumen tagihan.',
            );

            $this->transactionLifecycleService->transition(
                $transaction,
                TransactionStatus::REVISION_IN_PROGRESS,
                TransactionStep::VENDOR_INVOICE_INPUT,
                $actor,
                $notes ?: 'Approval ditolak dan dikembalikan ke Vendor untuk revisi dokumen tagihan.',
            );
        } elseif ($transaction->approvalTransactions()->where('status', ApprovalStatus::PENDING)->doesntExist()) {
            $this->transactionLifecycleService->transition(
                $transaction,
                TransactionStatus::ADMIN_GENERATE_DOCUMENTS,
                TransactionStep::INITIAL_DOCUMENT_GENERATION,
                $actor,
                'Approval Kepala Departemen dan Kepala Divisi selesai. Transaksi kembali ke Admin User untuk generate Lembar PPA dan Lembar Verifikasi.',
            );
        } elseif ($transaction->approvalTransactions()
            ->where('status', ApprovalStatus::PENDING)
            ->whereHas('approvalFlow', fn ($query) => $query->where('step_code', RoleCode::KEPALA_DEPARTEMEN->value))
            ->doesntExist()) {
            $this->transactionLifecycleService->transition(
                $transaction,
                TransactionStatus::WAITING_APPROVAL,
                TransactionStep::KADIV_REVIEW,
                $actor,
                'Approval Kepala Departemen selesai. Transaksi menunggu Approval Kepala Divisi.',
            );
        }

        $this->auditLogService->log(
            module: 'approval-workflow',
            action: strtolower($status->value),
            actor: $actor,
            transaction: $transaction,
            referenceType: ApprovalTransaction::class,
            referenceId: $approvalTransaction->id,
            oldValue: [],
            newValue: [
                'status' => $status->value,
                'notes' => $notes,
            ],
        );

        return $approvalTransaction->refresh();
    }

    protected function resolveApprover(string $roleCode, Transaction $transaction): User
    {
        $role = RoleCode::from($roleCode);

        $query = User::query()
            ->where('role_code', $role->value)
            ->where('is_active', true);

        if ($role === RoleCode::KEPALA_DEPARTEMEN || $role === RoleCode::ADMIN_DIVISI) {
            $query->where('department_id', $transaction->department_id);
        } else {
            $query->where('division_id', $transaction->division_id);
        }

        return $query->first()
            ?? User::query()->where('role_code', $role->value)->firstOrFail();
    }

    protected function ensurePpaApproverFlows(Transaction $transaction, string $documentCode): void
    {
        if (! $transaction->isPpa() || ! in_array($documentCode, [
            DocumentCode::PPA_LEMBAR_AWAL->value,
            DocumentCode::PPA_LEMBAR_VERIFIKASI->value,
        ], true)) {
            return;
        }

        ApprovalFlow::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('document_code', $documentCode)
            ->whereNotIn('step_code', [
                RoleCode::KEPALA_DEPARTEMEN->value,
                RoleCode::KEPALA_DIVISI->value,
            ])
            ->update(['is_required' => false]);

        foreach ([
            [1, RoleCode::KEPALA_DEPARTEMEN, 'Kepala Departemen'],
            [2, RoleCode::KEPALA_DIVISI, 'Kepala Divisi'],
        ] as [$stepNo, $roleCode, $stepName]) {
            ApprovalFlow::updateOrCreate(
                [
                    'transaction_type_id' => $transaction->transaction_type_id,
                    'document_code' => $documentCode,
                    'step_no' => $stepNo,
                ],
                [
                    'step_code' => $roleCode->value,
                    'step_name' => $stepName,
                    'is_required' => true,
                ],
            );
        }
    }

    protected function ensureStagedReviewFlows(Transaction $transaction, string $documentCode): void
    {
        foreach ([
            [1, RoleCode::KEPALA_DEPARTEMEN, 'Kepala Departemen'],
            [2, RoleCode::KEPALA_DIVISI, 'Kepala Divisi'],
        ] as [$stepNo, $roleCode, $stepName]) {
            ApprovalFlow::updateOrCreate(
                [
                    'transaction_type_id' => $transaction->transaction_type_id,
                    'document_code' => $documentCode,
                    'step_no' => $stepNo,
                ],
                [
                    'step_code' => $roleCode->value,
                    'step_name' => $stepName,
                    'is_required' => true,
                ],
            );
        }
    }

    protected function markVendorDocumentsForRevision(Transaction $transaction, User $actor, string $notes): void
    {
        $transaction->latestDocuments()
            ->where('source_actor', 'VENDOR')
            ->get()
            ->each(function ($document) use ($actor, $notes) {
                $document->update([
                    'status' => TransactionDocumentStatus::REVISION_REQUIRED,
                ]);

                VendorDocumentReview::updateOrCreate(
                    ['transaction_document_id' => $document->id],
                    [
                        'reviewed_by' => $actor->id,
                        'status' => VendorDocumentReviewStatus::REVISION_REQUIRED,
                        'notes' => $notes,
                        'reviewed_at' => now(),
                    ],
                );
            });
    }
}
