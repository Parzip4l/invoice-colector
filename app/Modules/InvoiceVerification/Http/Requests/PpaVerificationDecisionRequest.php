<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PpaVerificationDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sheet = $this->route('transaction')->ppaVerificationSheet;

        return $this->user()?->can('approve', $sheet) ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:APPROVED,REJECTED'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('decision') === 'REJECTED' && blank($this->input('notes'))) {
                $validator->errors()->add('notes', 'Catatan penolakan wajib diisi.');
            }
        });
    }
}
