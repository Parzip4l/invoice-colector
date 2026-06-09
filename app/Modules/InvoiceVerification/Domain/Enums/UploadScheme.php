<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum UploadScheme: string
{
    case PPA_DETAILED = 'PPA_DETAILED';
    case COMBINED = 'COMBINED';
}
