<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentFileController extends Controller
{
    public function preview(TransactionDocument $transactionDocument): StreamedResponse
    {
        $this->authorize('view', $transactionDocument);

        abort_unless($transactionDocument->file_disk && $transactionDocument->file_path, 404);
        abort_unless(Storage::disk($transactionDocument->file_disk)->exists($transactionDocument->file_path), 404);

        $fileName = $transactionDocument->file_name ?: basename($transactionDocument->file_path);
        $mimeType = $transactionDocument->mime_type ?: Storage::disk($transactionDocument->file_disk)->mimeType($transactionDocument->file_path);

        return Storage::disk($transactionDocument->file_disk)->response(
            $transactionDocument->file_path,
            $fileName,
            [
                'Content-Type' => $mimeType ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            ],
        );
    }
}
