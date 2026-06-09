<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum DocumentSourceType: string
{
    case SYSTEM = 'SYSTEM';
    case INTERNAL = 'INTERNAL';
    case VENDOR = 'VENDOR';
}
