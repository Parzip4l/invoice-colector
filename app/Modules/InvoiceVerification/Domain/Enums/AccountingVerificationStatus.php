<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum AccountingVerificationStatus: string
{
    case IN_PROGRESS = 'IN_PROGRESS';
    case REVISION_REQUIRED = 'REVISION_REQUIRED';
    case COMPLETED = 'COMPLETED';
}
