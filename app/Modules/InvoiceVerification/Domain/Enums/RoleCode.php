<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum RoleCode: string
{
    case ADMIN_DIVISI = 'ADMIN_DIVISI';
    case KEPALA_DEPARTEMEN = 'KEPALA_DEPARTEMEN';
    case KEPALA_DIVISI = 'KEPALA_DIVISI';
    case USER_DIVISI = 'USER_DIVISI';
    case VENDOR = 'VENDOR';
    case AKUNTANSI = 'AKUNTANSI';
    case FINANCE = 'FINANCE';

    public function label(): string
    {
        return config('invoice_verification.roles')[$this->value] ?? str($this->value)->replace('_', ' ')->title()->toString();
    }
}
