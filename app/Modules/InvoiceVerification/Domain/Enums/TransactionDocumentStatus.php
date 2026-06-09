<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum TransactionDocumentStatus: string
{
    case UPLOADED = 'UPLOADED';
    case UNDER_REVIEW = 'UNDER_REVIEW';
    case ACCEPTED = 'ACCEPTED';
    case REVISION_REQUIRED = 'REVISION_REQUIRED';
    case FINAL = 'FINAL';

    public function badgeClass(): string
    {
        return match ($this) {
            self::UPLOADED => 'bg-info-subtle text-info',
            self::UNDER_REVIEW => 'bg-primary-subtle text-primary',
            self::ACCEPTED => 'bg-success-subtle text-success',
            self::REVISION_REQUIRED => 'bg-danger-subtle text-danger',
            self::FINAL => 'bg-dark-subtle text-dark',
        };
    }
}
