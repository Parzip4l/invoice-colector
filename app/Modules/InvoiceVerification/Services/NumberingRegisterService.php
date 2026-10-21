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
            'ppaVerificationSheet',
        );
        $metadata = $transaction->invoiceMetadata;
        $register = NumberingRegister::firstOrNew(['transaction_id' => $transaction->id]);
        $taxDocument = $transaction->latestDocuments
            ->first(fn ($document) => in_array($document->documentType?->code?->value ?? $document->documentType?->code, ['PPA_FAKTUR_PAJAK', 'PNK_FAKTUR_PAJAK'], true));
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

        $invoiceValue = $metadata?->invoice_value;
        if ((empty($invoiceValue) || (float) $invoiceValue <= 0) && $transaction->isPpaNonContract() && $transaction->ppaVerificationSheet) {
            $budgetItems = (array) ($transaction->ppaVerificationSheet->budget_items_json ?: []);
            $totalRealization = (float) collect($budgetItems)->sum(fn ($item) => (float) ($item['nilai_realisasi'] ?? 0));
            if ($totalRealization > 0) {
                $invoiceValue = $totalRealization;
            }
        }

        $register->fill([
            'vendor_name' => $transaction->vendor?->name ?? $transaction->division?->name ?? '-',
            'received_date' => $metadata?->received_date ?? $register->received_date ?? now()->toDateString(),
            'invoice_number' => $metadata?->invoice_number ?: ($register->invoice_number ?: $transaction->registration_number),
            'invoice_date' => $metadata?->invoice_date ?? $register->invoice_date,
            'upload_date' => $register->upload_date ?? $firstUploadedAt?->toDateString(),
            'account_number' => $metadata?->account_number ?? $register->account_number,
            'account_name' => $metadata?->account_name ?? $register->account_name,
            'bank_name' => $metadata?->bank_name ?? $register->bank_name,
            'memo_number' => $metadata?->memo_number ?? $transaction->memoRequest?->memo_number ?? $register->memo_number,
            'contract_number' => $metadata?->contract_number ?? $transaction->agreementReference?->contract_number ?? $transaction->contract_number ?? $register->contract_number,
            'contract_value' => $metadata?->contract_value ?? $transaction->contract_value ?? $register->contract_value,
            'invoice_value' => $invoiceValue ?? $register->invoice_value,
            'ppn_percentage' => $metadata?->ppn_percentage ?? $register->ppn_percentage,
            'ppn_value' => $metadata?->ppn_value ?? $register->ppn_value,
            'pph_percentage' => $metadata?->pph_percentage ?? $register->pph_percentage,
            'pph_base_value' => $metadata?->pph_base_value ?? $register->pph_base_value,
            'pph_value' => $metadata?->pph_value ?? $register->pph_value,
            'tax_invoice_number' => $register->tax_invoice_number ?? data_get($taxDocument?->document_information_json, 'document_number'),
            'tax_invoice_date' => $register->tax_invoice_date ?? data_get($taxDocument?->document_information_json, 'document_date'),
            'payment_expedition_date' => $register->payment_expedition_date ?? $transaction->scheduled_payment_at?->toDateString(),
            'payment_date' => $register->payment_date ?? $transaction->paid_at?->toDateString(),
            'description' => $metadata?->description ?? $transaction->description ?? $register->description,
            'department_head_name' => $register->department_head_name ?? $departmentHeadName,
            'division_name' => $register->division_name ?? $transaction->division?->name,
            'generated_at' => $register->generated_at ?? now(),
        ]);

        $register->save();

        return $register;
    }
}
