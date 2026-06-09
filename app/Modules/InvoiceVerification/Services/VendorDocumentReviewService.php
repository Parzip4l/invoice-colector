<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionDocumentStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Enums\VendorDocumentReviewStatus;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;
use App\Modules\InvoiceVerification\Domain\Models\VendorDocumentReview;
use Illuminate\Support\Facades\DB;

class VendorDocumentReviewService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected TransactionLifecycleService $transactionLifecycleService,
        protected ApprovalWorkflowService $approvalWorkflowService,
    ) {
    }

    public function review(TransactionDocument $document, User $actor, VendorDocumentReviewStatus $status, ?string $notes = null): VendorDocumentReview
    {
        return DB::transaction(function () use ($document, $actor, $status, $notes) {
            $review = VendorDocumentReview::updateOrCreate(
                ['transaction_document_id' => $document->id],
                [
                    'reviewed_by' => $actor->id,
                    'status' => $status,
                    'notes' => $notes,
                    'reviewed_at' => now(),
                ],
            );

            $document->update([
                'status' => $status === VendorDocumentReviewStatus::ACCEPTED
                    ? TransactionDocumentStatus::ACCEPTED
                    : TransactionDocumentStatus::REVISION_REQUIRED,
            ]);

            $transaction = $document->transaction()->with('transactionType', 'generatedDocuments')->firstOrFail();

            $pendingVendorDocuments = $transaction
                ->latestDocuments()
                ->where('source_actor', 'VENDOR')
                ->where('status', TransactionDocumentStatus::UNDER_REVIEW)
                ->count();

            $hasRejectedVendorDocuments = $transaction
                ->latestDocuments()
                ->where('source_actor', 'VENDOR')
                ->where('status', TransactionDocumentStatus::REVISION_REQUIRED)
                ->exists();

            if ($status === VendorDocumentReviewStatus::ACCEPTED && $pendingVendorDocuments === 0 && ! $hasRejectedVendorDocuments) {
                $this->approvalWorkflowService->bootstrapForTransactionReview($transaction->fresh('transactionType'));
            }

            $this->transactionLifecycleService->transition(
                $transaction,
                $status === VendorDocumentReviewStatus::REVISION_REQUIRED
                    ? TransactionStatus::REVISION_IN_PROGRESS
                    : ($pendingVendorDocuments > 0 ? TransactionStatus::ADMIN_REVIEW : TransactionStatus::WAITING_APPROVAL),
                $status === VendorDocumentReviewStatus::REVISION_REQUIRED
                    ? TransactionStep::VENDOR_DOCUMENT_REVIEW
                    : ($pendingVendorDocuments > 0 ? TransactionStep::ADMIN_DOCUMENT_REVIEW : TransactionStep::KADEP_REVIEW),
                $actor,
                $notes ?: ($pendingVendorDocuments > 0 ? 'Review dokumen vendor diperbarui.' : 'Seluruh dokumen vendor disetujui. Transaksi diajukan ke Kepala Departemen.'),
            );

            $this->auditLogService->log(
                module: 'vendor-review',
                action: 'review_vendor_file',
                actor: $actor,
                transaction: $transaction,
                referenceType: TransactionDocument::class,
                referenceId: $document->id,
                newValue: [
                    'status' => $status->value,
                    'notes' => $notes,
                ],
            );

            return $review;
        });
    }
}
