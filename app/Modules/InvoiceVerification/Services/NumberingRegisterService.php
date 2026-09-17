<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Modules\InvoiceVerification\Domain\Models\NumberingRegister;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;

class NumberingRegisterService
{
    public function __construct(
        protected RegistrationNumberService $registrationNumberService,
    ) {
    }

    public function syncFromTransaction(Transaction $transaction): NumberingRegister
    {
        $transaction->loadMissing('invoiceMetadata', 'vendor', 'owner', 'memoRequest', 'agreementReference');
        $metadata = $transaction->invoiceMetadata;
        $register = NumberingRegister::firstOrNew(['transaction_id' => $transaction->id]);

        if (! $register->exists) {
            $register->register_number = $this->registrationNumberService->generateRegisterNumber();
        }

        $register->fill([
            'vendor_name' => $transaction->vendor?->name ?? $transaction->owner?->name ?? '-',
            'received_date' => $metadata?->received_date ?? now()->toDateString(),
            'invoice_number' => $metadata?->invoice_number ?: $transaction->registration_number,
            'invoice_date' => $metadata?->invoice_date,
            'account_number' => $metadata?->account_number,
            'account_name' => $metadata?->account_name,
            'bank_name' => $metadata?->bank_name,
            'memo_number' => $metadata?->memo_number ?? $transaction->memoRequest?->memo_number,
            'contract_number' => $metadata?->contract_number ?? $transaction->agreementReference?->contract_number ?? $transaction->contract_number,
            'contract_value' => $metadata?->contract_value ?? $transaction->contract_value,
            'invoice_value' => $metadata?->invoice_value,
            'ppn_value' => $metadata?->ppn_value,
            'pph_value' => $metadata?->pph_value,
            'description' => $metadata?->description ?? $transaction->description,
            'generated_at' => $register->generated_at ?? now(),
        ]);

        $register->save();

        return $register;
    }
}
