<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum GeneratedDocumentStatus: string
{
    case PENDING = 'PENDING';
    case GENERATED = 'GENERATED';
    case FAILED = 'FAILED';
}
