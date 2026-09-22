<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
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
        $transaction->loadMissing(
            'invoiceMetadata',
            'vendor',
            'owner',
            'memoRequest',
            'agreementReference',
            'transactionType',
            'division',
            'department.users',
            'latestDocuments.documentType',
        );
        $metadata = $transaction->invoiceMetadata;
        $register = NumberingRegister::firstOrNew(['transaction_id' => $transaction->id]);
        $taxDocument = $transaction->latestDocuments
            ->first(fn ($document) => ($document->documentType?->code?->value ?? $document->documentType?->code) === 'PPA_FAKTUR_PAJAK');
        $firstUploadedAt = $transaction->latestDocuments
            ->pluck('uploaded_at')
            ->filter()
            ->sort()
            ->first();
        $departmentHeadName = $transaction->department?->users
            ?->first(fn ($user) => ($user->role_code?->value ?? $user->role_code) === RoleCode::KEPALA_DEPARTEMEN->value)
            ?->name;

        if (! $register->exists) {
            $register->register_number = $this->registrationNumberService->generateRegisterNumber($transaction);
        }

        $register->fill([
            'vendor_name' => $transaction->vendor?->name ?? $transaction->owner?->name ?? '-',
            'received_date' => $metadata?->received_date ?? now()->toDateString(),
            'invoice_number' => $metadata?->invoice_number ?: $transaction->registration_number,
            'invoice_date' => $metadata?->invoice_date,
            'upload_date' => $register->upload_date ?? $firstUploadedAt?->toDateString(),
            'account_number' => $metadata?->account_number,
            'account_name' => $metadata?->account_name,
            'bank_name' => $metadata?->bank_name,
            'memo_number' => $metadata?->memo_number ?? $transaction->memoRequest?->memo_number,
            'contract_number' => $metadata?->contract_number ?? $transaction->agreementReference?->contract_number ?? $transaction->contract_number,
            'contract_value' => $metadata?->contract_value ?? $transaction->contract_value,
            'invoice_value' => $metadata?->invoice_value,
            'ppn_value' => $metadata?->ppn_value,
            'pph_value' => $metadata?->pph_value,
            'tax_invoice_number' => $register->tax_invoice_number ?? data_get($taxDocument?->document_information_json, 'document_number'),
            'tax_invoice_date' => $register->tax_invoice_date ?? data_get($taxDocument?->document_information_json, 'document_date'),
            'payment_expedition_date' => $register->payment_expedition_date ?? $transaction->scheduled_payment_at?->toDateString(),
            'payment_date' => $register->payment_date ?? $transaction->paid_at?->toDateString(),
            'description' => $metadata?->description ?? $transaction->description,
            'department_head_name' => $register->department_head_name ?? $departmentHeadName,
            'division_name' => $register->division_name ?? $transaction->division?->name,
            'generated_at' => $register->generated_at ?? now(),
        ]);

        $register->save();

        return $register;
    }
}
