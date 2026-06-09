<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReviewVendorDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('transactionDocument');

        return $this->user()?->can('reviewVendorDocument', $document) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:ACCEPTED,REVISION_REQUIRED'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('status') === 'REVISION_REQUIRED' && blank($this->input('notes'))) {
                $validator->errors()->add('notes', 'Catatan revisi wajib diisi ketika dokumen vendor perlu diperbaiki.');
            }
        });
    }
}
