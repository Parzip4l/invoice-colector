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

    public function compileTemporary(Transaction $transaction, array $documents): array
    {
        $fileName = sprintf('kompilasi-%s.pdf', Str::slug($transaction->registration_number));
        $temporaryPath = tempnam(sys_get_temp_dir(), 'iv_compiled_');

        if ($temporaryPath === false) {
            throw new \RuntimeException('File temporer untuk kompilasi PDF tidak dapat dibuat.');
        }

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

        try {
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

            $pdf->Output('F', $temporaryPath);
        } catch (\Throwable $exception) {
            @unlink($temporaryPath);

            throw $exception;
        } finally {
            foreach ($temporaryFiles as $temporaryFile) {
                @unlink($temporaryFile);
            }
        }

        return [
            'file_name' => $fileName,
            'temporary_path' => $temporaryPath,
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
