<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum AttachmentStatus: string
{
    case ATTACHED = 'ATTACHED';
    case NOT_ATTACHED = 'NOT_ATTACHED';
}
