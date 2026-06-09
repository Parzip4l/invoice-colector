<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProcessApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $approval = $this->route('approvalTransaction');

        return $this->user()?->can('process', $approval) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:APPROVED,REJECTED'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('status') === 'REJECTED' && blank($this->input('notes'))) {
                $validator->errors()->add('notes', 'Catatan wajib diisi jika approval ditolak.');
            }
        });
    }
}
