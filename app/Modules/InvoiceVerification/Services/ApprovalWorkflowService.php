<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalFlow;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalWorkflowService
{
    public function __construct(
        protected PortalApprovalResolverService $portalResolver,
        protected TransactionLifecycleService $lifecycleService,
        protected AuditLogService $auditLogService,
    ) {}

    /**
     * Check if all required documents for PPA Non Contract are uploaded.
     * If complete, idempotently initialize/update the 2 approval_transactions (Kadep & Kadiv)
     * and transition transaction status to WAITING_APPROVAL / KADEP_REVIEW.
     */
    public function triggerPpaNonContractApprovalIfComplete(Transaction $transaction, ?User $actor = null): bool
    {
        if (! $transaction->isPpaNonContract()) {
            throw ValidationException::withMessages(['approval' => 'Workflow ini hanya berlaku untuk PPA Non Kontrak.']);
        }

        $requiredTypeIds = DocumentType::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('is_required', true)
            ->pluck('id');

        if ($requiredTypeIds->isEmpty()) {
            throw ValidationException::withMessages(['approval' => 'Master dokumen wajib PPA Non Kontrak belum dikonfigurasi.']);
        }

        $uploadedTypeIds = $transaction->latestDocuments()
            ->pluck('document_type_id');

        if ($requiredTypeIds->diff($uploadedTypeIds)->isNotEmpty()) {
            $missing = DocumentType::query()
                ->whereIn('id', $requiredTypeIds->diff($uploadedTypeIds))
                ->pluck('name')
                ->implode(', ');
            throw ValidationException::withMessages([
                'approval' => 'Dokumen wajib belum lengkap: '.$missing.'.',
            ]);
        }

        $flows = ApprovalFlow::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('is_required', true)
            ->orderBy('step_no')
            ->get();

        $kadepFlow = $flows->firstWhere('step_no', 1);
        $kadivFlow = $flows->firstWhere('step_no', 2);

        if (! $kadepFlow || ! $kadivFlow) {
            throw ValidationException::withMessages(['approval' => 'Flow approval Kadep dan Kadiv belum dikonfigurasi.']);
        }

        $approvers = $this->portalResolver->resolveApprovers($transaction);
        $kadep = $approvers['kadep'];
        $kadiv = $approvers['kadiv'];

        $existingKadep = ApprovalTransaction::query()
            ->where('transaction_id', $transaction->id)
            ->where('approval_flow_id', $kadepFlow->id)
            ->first();

        $isRevision = in_array($transaction->status, [
            TransactionStatus::NOT_APPROVED,
            TransactionStatus::DRAFT,
        ], true);

        $kadepApproval = ApprovalTransaction::updateOrCreate(
            [
                'transaction_id' => $transaction->id,
                'approval_flow_id' => $kadepFlow->id,
            ],
            [
                'approver_user_id' => $kadep->id,
                'status' => ($existingKadep && ! $isRevision) ? $existingKadep->status : ApprovalStatus::PENDING,
                'notes' => ($existingKadep && ! $isRevision) ? $existingKadep->notes : null,
                'action_at' => ($existingKadep && ! $isRevision) ? $existingKadep->action_at : null,
                'portal_signature_id' => ($existingKadep && ! $isRevision) ? $existingKadep->portal_signature_id : null,
                'portal_signature_url' => ($existingKadep && ! $isRevision) ? $existingKadep->portal_signature_url : null,
                'portal_qr_url' => ($existingKadep && ! $isRevision) ? $existingKadep->portal_qr_url : null,
            ],
        );

        ApprovalTransaction::updateOrCreate(
            [
                'transaction_id' => $transaction->id,
                'approval_flow_id' => $kadivFlow->id,
            ],
            [
                'approver_user_id' => $kadiv->id,
                'status' => ApprovalStatus::PENDING,
                'notes' => null,
                'action_at' => null,
                'portal_signature_id' => null,
                'portal_signature_url' => null,
                'portal_qr_url' => null,
            ],
        );

        if ($transaction->status !== TransactionStatus::WAITING_APPROVAL) {
            $this->lifecycleService->transition(
                $transaction,
                TransactionStatus::WAITING_APPROVAL,
                TransactionStep::KADEP_REVIEW,
                $actor ?: $transaction->owner,
                'Seluruh dokumen wajib PPA Non Kontrak lengkap. Approval Kepala Departemen dimulai.',
            );
        }

        $this->portalResolver->notifyApprovalRequested($kadepApproval->fresh(['transaction.owner', 'approvalFlow', 'approver']));

        return true;
    }

    /**
     * Trigger Step 3 Approval: Kepala Divisi Keuangan dan Akuntansi (Kadiv Finance)
     * after Accounting completes verification.
     */
    public function triggerKadivFinanceApproval(Transaction $transaction, ?User $actor = null): ApprovalTransaction
    {
        $flow = ApprovalFlow::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('step_no', 3)
            ->first();

        if (! $flow) {
            throw ValidationException::withMessages([
                'approval' => 'Flow approval Kepala Divisi Keuangan dan Akuntansi belum dikonfigurasi.',
            ]);
        }

        $kadivFinance = $this->portalResolver->resolveKadivFinance($transaction);

        $approval = ApprovalTransaction::updateOrCreate(
            [
                'transaction_id' => $transaction->id,
                'approval_flow_id' => $flow->id,
            ],
            [
                'approver_user_id' => $kadivFinance->id,
                'status' => ApprovalStatus::PENDING,
                'notes' => null,
                'action_at' => null,
                'portal_signature_id' => null,
                'portal_signature_url' => null,
                'portal_qr_url' => null,
            ],
        );

        $resolvedActor = $actor
            ?: auth()->user()
            ?: $transaction->accountingVerification?->verifier
            ?: User::where('role_code', RoleCode::AKUNTANSI->value)->first()
            ?: $transaction->owner;

        $this->lifecycleService->transition(
            $transaction,
            TransactionStatus::WAITING_APPROVAL,
            TransactionStep::KADIV_FINANCE_REVIEW,
            $resolvedActor,
            'Verifikasi Akuntansi disetujui. Dokumen diajukan untuk persetujuan Kepala Divisi Keuangan dan Akuntansi (Kadiv Finance).',
        );

        $this->portalResolver->notifyApprovalRequested($approval->fresh(['transaction.owner', 'approvalFlow', 'approver']));

        try {
            app(PpaVerificationSheetService::class)->regeneratePdf($transaction);
        } catch (\Throwable) {
        }

        return $approval;
    }

    /**
     * Process an approval transaction (APPROVE or REJECT).
     */
    public function process(
        ApprovalTransaction $approval,
        User $actor,
        ApprovalStatus $targetStatus,
        ?string $notes = null,
    ): Transaction {
        return DB::transaction(function () use ($approval, $actor, $targetStatus, $notes) {
            $approval = ApprovalTransaction::query()
                ->with(['transaction.transactionType', 'approvalFlow'])
                ->lockForUpdate()
                ->findOrFail($approval->id);
            $transaction = $approval->transaction;

            if ($approval->status !== ApprovalStatus::PENDING) {
                throw ValidationException::withMessages([
                    'approval' => 'Approval ini sudah pernah diproses sebelumnya.',
                ]);
            }

            $stepNo = (int) $approval->approvalFlow->step_no;

            // Verify authorization for current step
            $isAuthorized = $approval->approver_user_id === $actor->id;

            if (! $isAuthorized) {
                throw ValidationException::withMessages([
                    'approval' => 'Anda tidak berwenang memproses approval pada tahap ini.',
                ]);
            }

            $expectedStep = match ($stepNo) {
                1 => TransactionStep::KADEP_REVIEW,
                2 => TransactionStep::KADIV_REVIEW,
                3 => TransactionStep::KADIV_FINANCE_REVIEW,
                default => null,
            };
            if ($expectedStep === null || $transaction->status !== TransactionStatus::WAITING_APPROVAL || $transaction->current_step !== $expectedStep) {
                throw ValidationException::withMessages([
                    'approval' => 'Tahap approval ini tidak sedang aktif pada transaksi.',
                ]);
            }

            // Kadep wajib selesai sebelum Kadiv dapat memproses
            if ($stepNo === 2) {
                $kadepApproval = ApprovalTransaction::query()
                    ->where('transaction_id', $transaction->id)
                    ->whereHas('approvalFlow', fn ($q) => $q->where('step_no', 1))
                    ->first();

                if (! $kadepApproval || $kadepApproval->status !== ApprovalStatus::APPROVED) {
                    throw ValidationException::withMessages([
                        'approval' => 'Approval Kepala Departemen wajib disetujui terlebih dahulu sebelum Kepala Divisi dapat memproses.',
                    ]);
                }
            }

            // Kadiv & Verifikasi Akuntansi wajib selesai sebelum Kadiv Finance dapat memproses
            if ($stepNo === 3) {
                $kadivApproval = ApprovalTransaction::query()
                    ->where('transaction_id', $transaction->id)
                    ->whereHas('approvalFlow', fn ($q) => $q->where('step_no', 2))
                    ->first();

                if (! $kadivApproval || $kadivApproval->status !== ApprovalStatus::APPROVED) {
                    throw ValidationException::withMessages([
                        'approval' => 'Approval Kepala Divisi wajib disetujui terlebih dahulu.',
                    ]);
                }

                if ($transaction->accountingVerification?->status !== \App\Modules\InvoiceVerification\Domain\Enums\AccountingVerificationStatus::COMPLETED) {
                    throw ValidationException::withMessages([
                        'approval' => 'Verifikasi Akuntansi wajib diselesaikan terlebih dahulu.',
                    ]);
                }
            }

            $roleLabel = $approval->approvalFlow->step_name ?? 'Approver';
            $oldApprovalStatus = $approval->status->value;

            if ($targetStatus === ApprovalStatus::REJECTED) {
                if (blank($notes)) {
                    throw ValidationException::withMessages([
                        'notes' => 'Catatan penolakan wajib diisi.',
                    ]);
                }

                $approval->forceFill([
                    'status' => ApprovalStatus::REJECTED,
                    'notes' => $notes,
                    'action_at' => now(),
                    'approver_user_id' => $actor->id,
                ])->save();

                $this->auditLogService->log(
                    module: 'approval',
                    action: 'reject',
                    actor: $actor,
                    transaction: $transaction,
                    referenceType: ApprovalTransaction::class,
                    referenceId: $approval->id,
                    oldValue: ['status' => $oldApprovalStatus],
                    newValue: ['status' => ApprovalStatus::REJECTED->value, 'notes' => $notes],
                );

                // Rejection returns transaction to pemohon and does NOT allow entry to Accounting
                $this->lifecycleService->transition(
                    $transaction,
                    TransactionStatus::NOT_APPROVED,
                    TransactionStep::INTERNAL_DOCUMENT_UPLOAD,
                    $actor,
                    sprintf('Ditolak oleh %s: %s', $roleLabel, $notes),
                );

                return $transaction->refresh();
            }

            if ($targetStatus === ApprovalStatus::APPROVED) {
                $signatureData = $this->portalResolver->requestPortalSignature($approval, $actor, $notes);

                $approval->forceFill([
                    'status' => ApprovalStatus::APPROVED,
                    'notes' => $notes,
                    'action_at' => now(),
                    'approver_user_id' => $actor->id,
                    'portal_signature_id' => $signatureData['portal_signature_id'] ?? null,
                    'portal_signature_url' => $signatureData['portal_signature_url'] ?? null,
                    'portal_qr_url' => $signatureData['portal_qr_url'] ?? null,
                ])->save();

                try {
                    app(PpaVerificationSheetService::class)->regeneratePdf($transaction);
                } catch (\Throwable) {
                    // PDF regeneration fallback
                }

                $this->auditLogService->log(
                    module: 'approval',
                    action: 'approve',
                    actor: $actor,
                    transaction: $transaction,
                    referenceType: ApprovalTransaction::class,
                    referenceId: $approval->id,
                    oldValue: ['status' => $oldApprovalStatus],
                    newValue: ['status' => ApprovalStatus::APPROVED->value, 'notes' => $notes],
                );

                if ($stepNo === 1) {
                    // Kadep approved -> advance to Kadiv Review
                    $this->lifecycleService->transition(
                        $transaction,
                        TransactionStatus::WAITING_APPROVAL,
                        TransactionStep::KADIV_REVIEW,
                        $actor,
                        'Disetujui oleh Kepala Departemen. Dokumen diteruskan ke Kepala Divisi.'.($notes ? ' Catatan: '.$notes : ''),
                    );
                    $kadivApproval = ApprovalTransaction::query()
                        ->with(['transaction.owner', 'approvalFlow', 'approver'])
                        ->where('transaction_id', $transaction->id)
                        ->whereHas('approvalFlow', fn ($query) => $query->where('step_no', 2))
                        ->first();
                    if ($kadivApproval) {
                        $this->portalResolver->notifyApprovalRequested($kadivApproval);
                    }
                } elseif ($stepNo === 2) {
                    // Kadiv approved -> status becomes SUBMITTED / ACCOUNTING_VERIFICATION
                    $transaction->forceFill(['submitted_at' => now()])->save();

                    $this->lifecycleService->transition(
                        $transaction,
                        TransactionStatus::SUBMITTED,
                        TransactionStep::ACCOUNTING_VERIFICATION,
                        $actor,
                        'Disetujui oleh Kepala Divisi. Dokumen diteruskan ke Accounting untuk verifikasi.'.($notes ? ' Catatan: '.$notes : ''),
                    );
                } elseif ($stepNo === 3) {
                    // Kadiv Finance approved -> status becomes COMPLETED and process is finished
                    $completedTransaction = $this->lifecycleService->markCompleted(
                        $transaction,
                        $actor,
                        'Disetujui oleh Kepala Divisi Keuangan dan Akuntansi (Kadiv Finance). Seluruh tahapan selesai.'.($notes ? ' Catatan: '.$notes : ''),
                    );
                    app(NumberingRegisterService::class)->syncFromTransaction($completedTransaction);

                    \Illuminate\Support\Facades\DB::afterCommit(function () use ($completedTransaction) {
                        $completedTransaction->loadMissing('transactionType', 'vendor');
                        $email = $completedTransaction->vendor?->contact_email ?? $completedTransaction->owner?->email;
                        if ($email) {
                            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\InvoiceTransactionReceivedMail($completedTransaction));
                        }
                    });
                }

                return $transaction->refresh();
            }

            throw ValidationException::withMessages([
                'approval' => 'Status approval tidak valid.',
            ]);
        });
    }
}
