<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SavePpaVerificationSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $this->user()?->can('update', $transaction->ppaVerificationSheet ?? \App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'size:9'],
            'items.*.document_type_id' => ['required', 'exists:document_types,id'],
            'items.*.attachment_status' => ['required', 'in:ATTACHED,NOT_ATTACHED'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $documentTypeIds = collect($this->input('items', []))->pluck('document_type_id');

            if ($documentTypeIds->duplicates()->isNotEmpty()) {
                $validator->errors()->add('items', 'Checklist PPA tidak boleh memiliki item dokumen yang sama lebih dari sekali.');
            }
        });
    }
}
