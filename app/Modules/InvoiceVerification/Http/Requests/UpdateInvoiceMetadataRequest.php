<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceMetadataRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['account_number'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => preg_replace('/[\s-]+/', '', (string) $this->input($field))]);
            }
        }
    }

    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $this->user()?->can('update', $transaction) ?? false;
    }

    public function rules(): array
    {
        $transaction = $this->route('transaction');
        $ignore = $transaction->invoiceMetadata?->id;

        return [
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'invoice_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('invoice_metadata', 'invoice_number')
                    ->ignore($ignore, 'id')
                    ->where(fn ($query) => $query->where('vendor_id', $this->input('vendor_id', $transaction->vendor_id))),
            ],
            'invoice_date' => ['nullable', 'date'],
            'account_number' => ['nullable', 'regex:/^\d{6,30}$/'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'contract_number' => ['nullable', 'string', 'max:255'],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'invoice_value' => ['nullable', 'numeric', 'min:0'],
            'ppn_value' => ['nullable', 'numeric', 'min:0'],
            'pph_value' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'received_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_number.regex' => 'Nomor rekening hanya boleh berisi angka, 6 sampai 30 digit.',
            'invoice_value.numeric' => 'Nilai invoice harus berupa angka.',
            'invoice_value.min' => 'Nilai invoice tidak boleh kurang dari 0.',
        ];
    }
}
