<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Modules\InvoiceVerification\Domain\Models\NumberingRegister;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
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

    public function generateRegisterNumber(Transaction|TransactionType|string $transactionType): string
    {
        $prefix = $this->resolveTransactionPrefix($transactionType);
        $year = now()->format('y');

        return DB::transaction(function () use ($prefix, $year) {
            $latestRegisterNumber = NumberingRegister::query()
                ->where('register_number', 'like', $prefix.'-'.$year.'-%')
                ->lockForUpdate()
                ->orderByDesc('register_number')
                ->value('register_number');

            $nextNumber = 1;

            if (
                is_string($latestRegisterNumber)
                && preg_match('/^'.preg_quote($prefix, '/').'-'.preg_quote($year, '/').'-(\d+)$/', $latestRegisterNumber, $matches)
            ) {
                $nextNumber = ((int) $matches[1]) + 1;
            }

            return sprintf('%s-%s-%05d', $prefix, $year, $nextNumber);
        });
    }

    private function resolveTransactionPrefix(Transaction|TransactionType|string $transactionType): string
    {
        if ($transactionType instanceof Transaction) {
            $transactionType->loadMissing('transactionType');
            $transactionType = $transactionType->transactionType;
        }

        if ($transactionType instanceof TransactionType && $transactionType->code) {
            return $this->registerPrefixForTypeCode($transactionType->code);
        }

        if (is_string($transactionType)) {
            $type = TransactionType::query()
                ->where('id', $transactionType)
                ->orWhere('code', $transactionType)
                ->first();

            if ($type?->code) {
                return $this->registerPrefixForTypeCode($type->code);
            }
        }

        throw new InvalidArgumentException('Jenis transaksi tidak dikenali untuk penomoran register.');
    }

    private function registerPrefixForTypeCode(TransactionTypeCode $typeCode): string
    {
        return match ($typeCode) {
            TransactionTypeCode::PPA,
            TransactionTypeCode::PPA_NON_CONTRACT => TransactionTypeCode::PPA->registrationPrefix(),
            default => $typeCode->registrationPrefix(),
        };
    }
}
