<?php

namespace App\Modules\InvoiceVerification\Services\Implementations;

use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet;
use App\Modules\InvoiceVerification\Services\Contracts\PpaVerificationSheetPdfGenerator;
use Illuminate\Support\Facades\Storage;

class PlaceholderPpaVerificationSheetPdfGenerator implements PpaVerificationSheetPdfGenerator
{
    public function generate(PpaVerificationSheet $sheet): array
    {
        $disk = config('invoice_verification.storage.documents_disk');
        $fileName = 'ppa-verification-sheet-'.$sheet->transaction->registration_number.'.pdf';
        $path = 'transactions/'.$sheet->transaction_id.'/generated/'.$fileName;

        Storage::disk($disk)->put($path, json_encode([
            'message' => 'Placeholder PPA verification sheet PDF.',
            'transaction_id' => $sheet->transaction_id,
            'status' => $sheet->status?->value,
            'generated_at' => now()->toIso8601String(),
            'items' => $sheet->items->map(fn ($item) => [
                'document_type' => $item->documentType?->name,
                'attachment_status' => $item->attachment_status?->value,
                'notes' => $item->notes,
            ]),
        ], JSON_PRETTY_PRINT));

        return [
            'file_name' => $fileName,
            'file_disk' => $disk,
            'file_path' => $path,
        ];
    }
}
