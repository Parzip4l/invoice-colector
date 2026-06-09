<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum DocumentUploadMode: string
{
    case SINGLE_SLOT = 'SINGLE_SLOT';
    case COMBINED_FORM = 'COMBINED_FORM';
    case STRUCTURED_FORM = 'STRUCTURED_FORM';
}
