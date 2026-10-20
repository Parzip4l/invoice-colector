<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum PpaVerificationSheetStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-secondary-subtle text-secondary',
            self::SUBMITTED => 'bg-info-subtle text-info',
            self::APPROVED => 'bg-success-subtle text-success',
            self::REJECTED => 'bg-danger-subtle text-danger',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }
}
