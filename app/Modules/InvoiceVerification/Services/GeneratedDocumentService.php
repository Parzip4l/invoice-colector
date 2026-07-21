<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalMode;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentCode;
use App\Modules\InvoiceVerification\Domain\Enums\GeneratedDocumentStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use App\Modules\InvoiceVerification\Domain\Models\GeneratedDocument;
use App\Modules\InvoiceVerification\Domain\Models\TemplateReference;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;

class GeneratedDocumentService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected GeneratedDocumentPdfService $generatedDocumentPdfService,
    ) {
    }

    public function generateInitialDocument(Transaction $transaction, User $actor): GeneratedDocument
    {
        $documentCode = match ($transaction->transactionType->code) {
            TransactionTypeCode::PPA => DocumentCode::PPA_LEMBAR_AWAL,
            TransactionTypeCode::SPU => DocumentCode::SPU_INITIAL_FORM,
            TransactionTypeCode::SPUK => DocumentCode::SPUK_INITIAL_FORM,
            TransactionTypeCode::KAS_KECIL => DocumentCode::KAS_KECIL_INITIAL_FORM,
        };

        $template = TemplateReference::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('document_code', $documentCode->value)
            ->first();

        $generatedDocument = GeneratedDocument::create([
            'transaction_id' => $transaction->id,
            'document_code' => $documentCode->value,
            'template_reference_id' => $template?->id,
            'document_number' => $transaction->registration_number,
            'version' => 1,
            'approval_mode' => ApprovalMode::MAIN_FLOW,
            'generation_status' => GeneratedDocumentStatus::GENERATED,
            'generated_by' => $actor->id,
            'generated_at' => now(),
        ]);

        $generatedDocument->update(
            $this->generatedDocumentPdfService->generateInitialDocumentPdf($transaction, $documentCode, $template)
        );

        $this->auditLogService->log(
            module: 'generated-documents',
            action: 'generate_document',
            actor: $actor,
            transaction: $transaction,
            referenceType: GeneratedDocument::class,
            referenceId: $generatedDocument->id,
            newValue: [
                'document_code' => $documentCode->value,
                'approval_mode' => ApprovalMode::MAIN_FLOW->value,
            ],
        );

        return $generatedDocument;
    }
}
