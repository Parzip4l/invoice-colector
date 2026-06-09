<?php

namespace App\Modules\InvoiceVerification\Services\Contracts;

use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet;

interface PpaVerificationSheetPdfGenerator
{
    public function generate(PpaVerificationSheet $sheet): array;
}
