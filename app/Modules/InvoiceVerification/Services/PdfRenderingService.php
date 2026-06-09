<?php

namespace App\Modules\InvoiceVerification\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfRenderingService
{
    public function renderToDisk(
        string $view,
        array $data,
        string $disk,
        string $path,
        string $paper = 'a4',
        string $orientation = 'portrait',
    ): void {
        Storage::disk($disk)->put(
            $path,
            $this->renderToString($view, $data, $paper, $orientation),
        );
    }

    public function renderToString(
        string $view,
        array $data,
        string $paper = 'a4',
        string $orientation = 'portrait',
    ): string {
        return Pdf::loadView($view, $data)
            ->setPaper($paper, $orientation)
            ->output();
    }

    public function renderToTempPath(
        string $view,
        array $data,
        string $paper = 'a4',
        string $orientation = 'portrait',
    ): string {
        $tempPath = tempnam(sys_get_temp_dir(), 'iv_pdf_');

        file_put_contents(
            $tempPath,
            $this->renderToString($view, $data, $paper, $orientation),
        );

        return $tempPath;
    }
}
