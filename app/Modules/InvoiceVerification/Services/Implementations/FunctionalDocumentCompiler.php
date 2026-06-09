<?php

namespace App\Modules\InvoiceVerification\Services\Implementations;

use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Services\Contracts\DocumentCompiler;
use App\Modules\InvoiceVerification\Services\PdfRenderingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class FunctionalDocumentCompiler implements DocumentCompiler
{
    public function __construct(
        protected PdfRenderingService $pdfRenderingService,
    ) {
    }

    public function compile(Transaction $transaction, array $documents, string $targetDirectory): array
    {
        $disk = config('invoice_verification.storage.compiled_disk');
        $fileName = sprintf('compiled-%s-%s.pdf', $transaction->registration_number, Str::lower(Str::random(6)));
        $path = trim($targetDirectory, '/').'/'.$fileName;

        $pdf = new Fpdi();
        $temporaryFiles = [];

        if ($documents === []) {
            $documents[] = [
                'label' => 'No documents available',
                'path' => null,
                'disk' => null,
                'file_name' => 'missing-document',
                'extension' => null,
            ];
        }

        foreach ($documents as $document) {
            $sourcePath = $this->prepareSourcePdf($document, $temporaryFiles, $transaction);

            if (! $sourcePath || ! is_file($sourcePath)) {
                continue;
            }

            $pageCount = $pdf->setSourceFile($sourcePath);

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $templateId = $pdf->importPage($pageNumber);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        }

        Storage::disk($disk)->put($path, $pdf->Output('S'));

        foreach ($temporaryFiles as $temporaryFile) {
            @unlink($temporaryFile);
        }

        return [
            'compiled_file_name' => $fileName,
            'compiled_file_disk' => $disk,
            'compiled_file_path' => $path,
            'total_files' => count($documents),
        ];
    }

    protected function prepareSourcePdf(array $document, array &$temporaryFiles, Transaction $transaction): ?string
    {
        $localPath = $this->resolveLocalPath($document);

        if ($localPath) {
            $temporaryFiles[] = $localPath;
        }

        $extension = strtolower((string) ($document['extension'] ?? pathinfo((string) ($document['file_name'] ?? $localPath), PATHINFO_EXTENSION)));

        if ($localPath && $extension === 'pdf') {
            return $localPath;
        }

        if ($localPath && in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            $tempPath = $this->pdfRenderingService->renderToTempPath(
                'invoice-verification.pdf.image-attachment',
                [
                    'transaction' => $transaction,
                    'document' => $document,
                    'imageDataUri' => $this->imageDataUri($localPath, $extension),
                ],
            );

            $temporaryFiles[] = $tempPath;

            return $tempPath;
        }

        $tempPath = $this->pdfRenderingService->renderToTempPath(
            'invoice-verification.pdf.document-cover',
            [
                'transaction' => $transaction,
                'document' => $document,
                'localPath' => $localPath,
            ],
        );

        $temporaryFiles[] = $tempPath;

        return $tempPath;
    }

    protected function resolveLocalPath(array $document): ?string
    {
        $disk = $document['disk'] ?? null;
        $path = $document['path'] ?? null;

        if (! $disk || ! $path || ! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $stream = Storage::disk($disk)->readStream($path);

        if (! is_resource($stream)) {
            return null;
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'iv_src_');
        $target = fopen($temporaryPath, 'wb');

        stream_copy_to_stream($stream, $target);

        if (is_resource($stream)) {
            fclose($stream);
        }

        if (is_resource($target)) {
            fclose($target);
        }

        return $temporaryPath;
    }

    protected function imageDataUri(string $path, string $extension): string
    {
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
