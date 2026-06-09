<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum ApprovalMode: string
{
    case MAIN_FLOW = 'MAIN_FLOW';
    case DIVISION_ONLY = 'DIVISION_ONLY';
    case NONE = 'NONE';
}
