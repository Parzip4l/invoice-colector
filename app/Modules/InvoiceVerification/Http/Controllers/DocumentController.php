<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Actions\UploadTransactionDocumentAction;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceActor;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\InvoiceMetadata;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
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

        $metadata = InvoiceMetadata::updateOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'vendor_id' => $transaction->vendor_id,
                'invoice_number' => $payload['invoice_number'],
                'invoice_date' => $payload['invoice_date'] ?? null,
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

        return redirect()
            ->route('invoice-verification.transactions.documents.show', $transaction)
            ->with('success', 'Data tagihan dan dokumen PPA berhasil disubmit ke Admin User untuk verifikasi.');
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
