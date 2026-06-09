<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum VendorDocumentReviewStatus: string
{
    case ACCEPTED = 'ACCEPTED';
    case REVISION_REQUIRED = 'REVISION_REQUIRED';
}
