<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum AccountingVerificationItemStatus: string
{
    case VALID = 'VALID';
    case REVISION_REQUIRED = 'REVISION_REQUIRED';
    case MISMATCH = 'MISMATCH';

    public function badgeClass(): string
    {
        return match ($this) {
            self::VALID => 'bg-success-subtle text-success',
            self::REVISION_REQUIRED => 'bg-danger-subtle text-danger',
            self::MISMATCH => 'bg-warning-subtle text-warning',
        };
    }
}
