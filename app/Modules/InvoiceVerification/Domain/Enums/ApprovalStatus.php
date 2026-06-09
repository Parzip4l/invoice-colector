<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum ApprovalStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-warning-subtle text-warning',
            self::APPROVED => 'bg-success-subtle text-success',
            self::REJECTED => 'bg-danger-subtle text-danger',
        };
    }
}
