<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAccountingVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $this->user()?->can('verifyAccounting', $transaction) ?? false;
    }

    public function rules(): array
    {
        return [
            'administration_status' => ['required', 'in:VALID,REVISION_REQUIRED'],
            'administration_notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.transaction_document_id' => ['required', 'exists:transaction_documents,id'],
            'items.*.status' => ['required', 'in:VALID,REVISION_REQUIRED'],
            'items.*.notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('administration_status') === 'REVISION_REQUIRED' && blank($this->input('administration_notes'))) {
                $validator->errors()->add('administration_notes', 'Catatan Administration wajib diisi ketika dokumen Administration direject.');
            }

            foreach ((array) $this->input('items', []) as $index => $item) {
                if (($item['status'] ?? null) === 'REVISION_REQUIRED' && blank($item['notes'] ?? null)) {
                    $validator->errors()->add("items.$index.notes", 'Keterangan revisi wajib diisi ketika dokumen Invoicing direject.');
                }
            }
        });
    }
}
