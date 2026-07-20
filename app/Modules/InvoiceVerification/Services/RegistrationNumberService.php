<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Modules\InvoiceVerification\Domain\Models\NumberingRegister;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrationNumberService
{
    public function generateTransactionNumber(TransactionType|string $transactionType): string
    {
        $type = $transactionType instanceof TransactionType
            ? $transactionType
            : TransactionType::query()->where('id', $transactionType)->orWhere('code', $transactionType)->first();

        if (! $type || ! $type->code) {
            throw new InvalidArgumentException('Jenis transaksi tidak dikenali untuk penomoran.');
        }

        $prefix = $type->code->registrationPrefix();

        return DB::transaction(function () use ($prefix) {
            DB::table('transaction_number_sequences')->updateOrInsert(
                ['prefix' => $prefix],
                ['updated_at' => now(), 'created_at' => now()],
            );

            $sequence = DB::table('transaction_number_sequences')
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            $nextNumber = ((int) $sequence->last_number) + 1;

            DB::table('transaction_number_sequences')
                ->where('prefix', $prefix)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            return sprintf('%s-%05d', $prefix, $nextNumber);
        });
    }

    public function generateRegisterNumber(): string
    {
        $count = NumberingRegister::query()
            ->whereYear('created_at', now()->year)
            ->count() + 1;

        return sprintf('REG/%s/%05d', now()->format('Ym'), $count);
    }
}
