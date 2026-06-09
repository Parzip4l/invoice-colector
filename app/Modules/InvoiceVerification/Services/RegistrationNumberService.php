<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Modules\InvoiceVerification\Domain\Models\NumberingRegister;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;

class RegistrationNumberService
{
    public function generateTransactionNumber(): string
    {
        $count = Transaction::query()
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('TRX/%s/%04d', now()->format('Ymd'), $count);
    }

    public function generateRegisterNumber(): string
    {
        $count = NumberingRegister::query()
            ->whereYear('created_at', now()->year)
            ->count() + 1;

        return sprintf('REG/%s/%05d', now()->format('Ym'), $count);
    }
}
