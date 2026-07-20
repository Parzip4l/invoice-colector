<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Mail\InvoiceTransactionReceivedMail;
use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\AccountingVerificationItemStatus;
use App\Modules\InvoiceVerification\Domain\Enums\AccountingVerificationStatus;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceActor;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionDocumentStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Enums\VendorDocumentReviewStatus;
use App\Modules\InvoiceVerification\Domain\Models\AccountingVerification;
use App\Modules\InvoiceVerification\Domain\Models\AccountingVerificationItem;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\VendorDocumentReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AccountingVerificationService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected TransactionLifecycleService $transactionLifecycleService,
        protected FinalizationService $finalizationService,
        protected PpaVerificationSheetService $ppaVerificationSheetService,
    ) {
    }

    public function getOrCreate(Transaction $transaction, User $actor): AccountingVerification
    {
        $verification = AccountingVerification::firstOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'verifier_user_id' => $actor->id,
                'status' => AccountingVerificationStatus::IN_PROGRESS,
            ],
        );

        $eligibleDocuments = $transaction->latestDocuments()
            ->where(function ($query) {
                $query->where('source_actor', DocumentSourceActor::USER_DIVISI->value)
                    ->orWhere(function ($vendorQuery) {
                        $vendorQuery->where('source_actor', DocumentSourceActor::VENDOR->value)
                            ->whereIn('status', ['ACCEPTED', 'UNDER_REVIEW', 'UPLOADED']);
                    });
            })
            ->get();

        foreach ($eligibleDocuments as $document) {
            AccountingVerificationItem::firstOrCreate(
                [
                    'accounting_verification_id' => $verification->id,
                    'transaction_document_id' => $document->id,
                ],
                [
                    'status' => AccountingVerificationItemStatus::VALID,
                ],
            );
        }

        return $verification->fresh('items.transactionDocument.documentType');
    }

    public function startReview(Transaction $transaction, User $actor): Transaction
    {
        return $this->transactionLifecycleService->startAccountingReview($transaction, $actor);
    }

    public function verify(
        Transaction $transaction,
        User $actor,
        array $items,
        string $administrationStatus,
        ?string $administrationNotes = null,
        ?string $notes = null,
    ): AccountingVerification
    {
        return DB::transaction(function () use ($transaction, $actor, $items, $administrationStatus, $administrationNotes, $notes) {
            $transaction->refresh();

            if ($transaction->status === TransactionStatus::ACCOUNTING_VERIFICATION) {
                $transaction = $this->transactionLifecycleService->startAccountingReview($transaction, $actor);
            }

            if ($transaction->status !== TransactionStatus::IN_REVIEW) {
                throw ValidationException::withMessages([
                    'status' => 'Transaksi harus berstatus In Review sebelum verifikasi diselesaikan.',
                ]);
            }

            $verification = $this->getOrCreate($transaction, $actor);
            $mismatchMap = collect($this->ppaVerificationSheetService->mismatchSummary($transaction))
                ->keyBy('document_name');

            foreach ($items as $item) {
                $verificationItem = AccountingVerificationItem::query()
                    ->where('accounting_verification_id', $verification->id)
                    ->where('transaction_document_id', $item['transaction_document_id'])
                    ->firstOrFail();

                $status = AccountingVerificationItemStatus::from($item['status']);
                $documentName = $verificationItem->transactionDocument->documentType->name;

                if ($mismatchMap->has($documentName) && $mismatchMap[$documentName]['is_mismatch']) {
                    $status = AccountingVerificationItemStatus::MISMATCH;
                }

                $verificationItem->update([
                    'status' => $status,
                    'notes' => $item['notes'] ?? null,
                    'verified_at' => now(),
                ]);

                if (in_array($status, [
                    AccountingVerificationItemStatus::REVISION_REQUIRED,
                    AccountingVerificationItemStatus::MISMATCH,
                ], true)) {
                    $verificationItem->transactionDocument->update([
                        'status' => TransactionDocumentStatus::REVISION_REQUIRED,
                    ]);
                }
            }

            $hasIssue = $verification->items()
                ->whereIn('status', [
                    AccountingVerificationItemStatus::REVISION_REQUIRED,
                    AccountingVerificationItemStatus::MISMATCH,
                ])
                ->exists();
            $hasAdministrationIssue = $administrationStatus === AccountingVerificationItemStatus::REVISION_REQUIRED->value;

            $verification->update([
                'verifier_user_id' => $actor->id,
                'status' => ($hasIssue || $hasAdministrationIssue) ? AccountingVerificationStatus::REVISION_REQUIRED : AccountingVerificationStatus::COMPLETED,
                'notes' => trim(implode("\n", array_filter([
                    $administrationNotes ? 'Administration: '.$administrationNotes : null,
                    $notes,
                ]))) ?: null,
                'verified_at' => now(),
            ]);

            $this->auditLogService->log(
                module: 'accounting-verification',
                action: 'accounting_verification',
                actor: $actor,
                transaction: $transaction,
                referenceType: AccountingVerification::class,
                referenceId: $verification->id,
                newValue: [
                    'status' => $verification->status->value,
                    'administration_status' => $administrationStatus,
                    'administration_notes' => $administrationNotes,
                    'notes' => $notes,
                ],
            );

            if ($hasAdministrationIssue) {
                $this->markVendorDocumentsForRevision($transaction, $actor, $administrationNotes ?: 'Accounting meminta revisi dokumen tagihan.');

                $this->transactionLifecycleService->markNotApproved($transaction, $actor, $administrationNotes ?: 'Accounting meminta revisi dokumen tagihan.');

                $this->auditLogService->log(
                    module: 'notifications',
                    action: 'notify_admin_accounting_revision',
                    actor: $actor,
                    transaction: $transaction,
                    referenceType: AccountingVerification::class,
                    referenceId: $verification->id,
                    newValue: ['notes' => $administrationNotes],
                );

                return $verification->fresh('items.transactionDocument.documentType');
            }

            if ($hasIssue) {
                $this->transactionLifecycleService->markNotApproved($transaction, $actor, $notes ?: 'Accounting meminta revisi dokumen Invoicing ke Vendor.');

                $this->auditLogService->log(
                    module: 'notifications',
                    action: 'notify_admin_accounting_revision',
                    actor: $actor,
                    transaction: $transaction,
                    referenceType: AccountingVerification::class,
                    referenceId: $verification->id,
                    newValue: ['notes' => $notes],
                );

                return $verification->fresh('items.transactionDocument.documentType');
            }

            $receivedTransaction = $this->transactionLifecycleService->markReceived($transaction, $actor);

            DB::afterCommit(function () use ($receivedTransaction) {
                $receivedTransaction->loadMissing('transactionType', 'vendor');
                $email = $receivedTransaction->vendor?->contact_email ?? $receivedTransaction->owner?->email;

                if ($email) {
                    Mail::to($email)->send(new InvoiceTransactionReceivedMail($receivedTransaction));
                }
            });

            return $verification->fresh('items.transactionDocument.documentType');
        });
    }

    protected function markVendorDocumentsForRevision(Transaction $transaction, User $actor, string $notes): void
    {
        $transaction->latestDocuments()
            ->where('source_actor', 'VENDOR')
            ->get()
            ->each(function ($document) use ($actor, $notes) {
                $document->update(['status' => TransactionDocumentStatus::REVISION_REQUIRED]);

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
