<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum PpaVerificationSheetStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
}
