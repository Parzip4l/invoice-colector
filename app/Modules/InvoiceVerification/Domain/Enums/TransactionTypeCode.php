<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum TransactionTypeCode: string
{
    case PPA = 'PPA';
    case PPA_NON_CONTRACT = 'PPA_NON_CONTRACT';
    case SPU = 'SPU';
    case SPUK = 'SPUK';
    case KAS_KECIL = 'KAS_KECIL';

    public function label(): string
    {
        return match ($this) {
            self::PPA => 'PPA Kontrak',
            self::PPA_NON_CONTRACT => 'PPA Non Kontrak',
            self::SPU => 'SPU',
            self::SPUK => 'SPUK',
            self::KAS_KECIL => 'Kas Kecil',
        };
    }

    public function registrationPrefix(): string
    {
        return match ($this) {
            self::PPA => 'PPA',
            self::PPA_NON_CONTRACT => 'PNK',
            self::SPU => 'SPU',
            self::SPUK => 'SPUK',
            self::KAS_KECIL => 'KK',
        };
    }

    public function isInternalVendorType(): bool
    {
        return $this !== self::PPA;
    }
}
