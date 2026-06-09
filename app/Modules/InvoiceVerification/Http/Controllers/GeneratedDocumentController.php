<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Models\GeneratedDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneratedDocumentController extends Controller
{
    public function show(GeneratedDocument $generatedDocument)
    {
        $this->authorize('view', $generatedDocument);

        $generatedDocument->load(['transaction', 'approvals.approvalFlow', 'generator', 'templateReference']);

        return view('invoice-verification.generated-documents.show', compact('generatedDocument'));
    }

    public function preview(GeneratedDocument $generatedDocument): StreamedResponse
    {
        $this->authorize('view', $generatedDocument);

        abort_unless($generatedDocument->file_disk && $generatedDocument->file_path, 404);
        abort_unless(Storage::disk($generatedDocument->file_disk)->exists($generatedDocument->file_path), 404);

        $fileName = $generatedDocument->file_name ?: basename($generatedDocument->file_path);
        $mimeType = $generatedDocument->mime_type ?: Storage::disk($generatedDocument->file_disk)->mimeType($generatedDocument->file_path);

        return Storage::disk($generatedDocument->file_disk)->response(
            $generatedDocument->file_path,
            $fileName,
            [
                'Content-Type' => $mimeType ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            ],
        );
    }
}
