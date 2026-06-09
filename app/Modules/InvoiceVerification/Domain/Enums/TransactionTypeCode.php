<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum TransactionTypeCode: string
{
    case PPA = 'PPA';
    case SPU = 'SPU';
    case SPUK = 'SPUK';
    case KAS_KECIL = 'KAS_KECIL';

    public function label(): string
    {
        return match ($this) {
            self::PPA => 'PPA',
            self::SPU => 'SPU',
            self::SPUK => 'SPUK',
            self::KAS_KECIL => 'Kas Kecil',
        };
    }
}
