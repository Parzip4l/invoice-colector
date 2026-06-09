<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Modules\InvoiceVerification\Domain\Enums\DocumentCode;
use App\Modules\InvoiceVerification\Domain\Models\TemplateReference;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;

class GeneratedDocumentPdfService
{
    public function __construct(
        protected PdfRenderingService $pdfRenderingService,
    ) {
    }

    public function generateInitialDocumentPdf(
        Transaction $transaction,
        DocumentCode $documentCode,
        ?TemplateReference $template = null,
    ): array {
        $disk = config('invoice_verification.storage.documents_disk');
        $fileName = $documentCode->value.'.pdf';
        $path = 'transactions/'.$transaction->id.'/generated/'.$fileName;

        $this->pdfRenderingService->renderToDisk(
            'invoice-verification.pdf.generated-document',
            [
                'transaction' => $transaction->loadMissing('transactionType', 'vendor', 'division', 'department', 'invoiceMetadata'),
                'documentCode' => $documentCode,
                'template' => $template,
                'generatedAt' => now(),
            ],
            $disk,
            $path,
        );

        return [
            'file_name' => $fileName,
            'file_disk' => $disk,
            'file_path' => $path,
        ];
    }
}
