<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Actions\UploadTransactionDocumentAction;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceActor;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionDocumentStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\InvoiceMetadata;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionStatusHistory;
use App\Modules\InvoiceVerification\Http\Requests\UploadCombinedDocumentsRequest;
use App\Modules\InvoiceVerification\Http\Requests\UploadPpaDocumentsRequest;

class DocumentController extends Controller
{
    public function __construct(
        protected UploadTransactionDocumentAction $uploadTransactionDocumentAction,
    ) {
    }

    public function show(Transaction $transaction)
    {
        $this->authorize('view', $transaction);

        $transaction->load([
            'transactionType',
            'latestDocuments.documentType',
            'latestDocuments.vendorReview',
            'latestDocuments.accountingVerificationItems',
            'memoRequest',
            'agreementReference',
            'vendor.defaultBank',
            'invoiceMetadata',
        ]);
        $documentTypes = DocumentType::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->orderBy('sort_order')
            ->get();

        $view = $transaction->isPpa()
            ? 'invoice-verification.documents.ppa-upload'
            : 'invoice-verification.documents.combined-upload';

        return view($view, compact('transaction', 'documentTypes'));
    }

    public function storePpa(UploadPpaDocumentsRequest $request, Transaction $transaction)
    {
        $payload = $request->validated();
        $invoiceNumber = $payload['invoice_number'] ?? $transaction->invoiceMetadata?->invoice_number ?? $transaction->registration_number;
        $invoiceDate = $payload['invoice_date'] ?? $transaction->invoiceMetadata?->invoice_date;

        $metadata = InvoiceMetadata::updateOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'vendor_id' => $transaction->vendor_id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'account_number' => $payload['account_number'] ?? null,
                'account_name' => $payload['account_name'] ?? null,
                'bank_name' => $payload['bank_name'] ?? null,
                'memo_number' => $transaction->memoRequest?->memo_number,
                'contract_number' => $transaction->agreementReference?->contract_number,
                'contract_value' => $transaction->agreementReference?->contract_value,
                'invoice_value' => $payload['invoice_value'] ?? null,
                'ppn_value' => $payload['ppn_value'] ?? null,
                'description' => $payload['description'] ?? $transaction->description,
                'received_date' => $payload['received_date'] ?? now()->toDateString(),
            ],
        );

        $transaction->update([
            'title' => $metadata->invoice_number.' - '.($metadata->contract_number ?? $transaction->registration_number),
            'description' => $metadata->description ?? $transaction->description,
            'submitted_at' => now(),
        ]);

        foreach ($payload['documents'] as $item) {
            $documentType = DocumentType::findOrFail($item['document_type_id']);

            $this->uploadTransactionDocumentAction->execute(
                transaction: $transaction,
                documentType: $documentType,
                file: $item['file'],
                sourceActor: DocumentSourceActor::VENDOR,
                user: $request->user(),
                vendor: $transaction->vendor,
                documentInformation: $item['document_information'] ?? null,
            );
        }

        $this->markPpaReadyForAdminReview($transaction->fresh(), $request->user());

        return redirect()
            ->route('invoice-verification.transactions.documents.show', $transaction)
            ->with('success', 'Data tagihan dan dokumen PPA berhasil disubmit ke Admin User untuk verifikasi.');
    }

    private function markPpaReadyForAdminReview(Transaction $transaction, $actor): void
    {
        if (! $transaction->isPpa()) {
            return;
        }

        if (! in_array($transaction->status, [
            TransactionStatus::DRAFT,
            TransactionStatus::VENDOR_INPUT,
            TransactionStatus::REVISION_IN_PROGRESS,
            TransactionStatus::NOT_APPROVED,
        ], true)) {
            return;
        }

        $requiredTypeIds = DocumentType::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('source_type', DocumentSourceActor::VENDOR->value)
            ->where('is_required', true)
            ->pluck('id');

        if ($requiredTypeIds->isEmpty()) {
            return;
        }

        $uploadedTypeIds = $transaction->latestDocuments()
            ->where('source_actor', DocumentSourceActor::VENDOR->value)
            ->whereIn('status', [
                TransactionDocumentStatus::UNDER_REVIEW->value,
                TransactionDocumentStatus::ACCEPTED->value,
                TransactionDocumentStatus::UPLOADED->value,
            ])
            ->pluck('document_type_id');

        if ($requiredTypeIds->diff($uploadedTypeIds)->isNotEmpty()) {
            return;
        }

        $fromStatus = $transaction->status?->value;
        $fromStep = $transaction->current_step?->value;

        $transaction->forceFill([
            'status' => TransactionStatus::ADMIN_REVIEW,
            'current_step' => TransactionStep::ADMIN_DOCUMENT_REVIEW,
        ])->save();

        TransactionStatusHistory::create([
            'transaction_id' => $transaction->id,
            'from_status' => $fromStatus,
            'to_status' => TransactionStatus::ADMIN_REVIEW->value,
            'from_step' => $fromStep,
            'to_step' => TransactionStep::ADMIN_DOCUMENT_REVIEW->value,
            'changed_by' => $actor?->id,
            'notes' => 'Dokumen vendor wajib lengkap dan siap direview Admin User.',
        ]);
    }

    public function storeCombined(UploadCombinedDocumentsRequest $request, Transaction $transaction)
    {
        foreach ($request->validated('attachments') as $item) {
            $documentType = DocumentType::findOrFail($item['document_type_id']);
            $actor = DocumentSourceActor::from($item['source_actor']);

            $this->uploadTransactionDocumentAction->execute(
                transaction: $transaction,
                documentType: $documentType,
                file: $item['file'],
                sourceActor: $actor,
                user: $request->user(),
                vendor: $actor === DocumentSourceActor::VENDOR ? $transaction->vendor : null,
                documentLabel: $item['document_label'],
                documentInformation: $item['document_information'] ?? null,
            );
        }

        return redirect()
            ->route('invoice-verification.transactions.documents.show', $transaction)
            ->with('success', 'Dokumen berhasil diunggah melalui form gabungan.');
    }
}
