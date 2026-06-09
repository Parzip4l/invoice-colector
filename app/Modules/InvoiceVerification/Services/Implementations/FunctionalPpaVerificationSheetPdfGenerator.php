<?php

namespace App\Modules\InvoiceVerification\Services\Implementations;

use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet;
use App\Modules\InvoiceVerification\Services\Contracts\PpaVerificationSheetPdfGenerator;
use App\Modules\InvoiceVerification\Services\PdfRenderingService;

class FunctionalPpaVerificationSheetPdfGenerator implements PpaVerificationSheetPdfGenerator
{
    public function __construct(
        protected PdfRenderingService $pdfRenderingService,
    ) {
    }

    public function generate(PpaVerificationSheet $sheet): array
    {
        $disk = config('invoice_verification.storage.documents_disk');
        $fileName = 'ppa-verification-sheet-'.$sheet->transaction->registration_number.'.pdf';
        $path = 'transactions/'.$sheet->transaction_id.'/generated/'.$fileName;

        $this->pdfRenderingService->renderToDisk(
            'invoice-verification.pdf.ppa-verification-sheet',
            [
                'sheet' => $sheet->loadMissing('transaction.transactionType', 'transaction.vendor', 'items.documentType', 'filledBy', 'approvedBy'),
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
