<?php

namespace App\Modules\InvoiceVerification\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SignatureQrService
{
    public function generateBase64Svg(?string $content, int $size = 90): ?string
    {
        if (blank($content)) {
            return null;
        }

        try {
            $svg = QrCode::format('svg')
                ->size($size)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($content);

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (\Throwable) {
            return null;
        }
    }
}
