<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum DocumentSourceActor: string
{
    case USER_DIVISI = 'USER_DIVISI';
    case VENDOR = 'VENDOR';
    case SYSTEM = 'SYSTEM';
}
