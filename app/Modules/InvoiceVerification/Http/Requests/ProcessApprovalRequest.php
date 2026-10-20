<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([ApprovalStatus::APPROVED->value, ApprovalStatus::REJECTED->value]),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(fn () => $this->input('status') === ApprovalStatus::REJECTED->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status keputusan approval wajib dipilih.',
            'status.in' => 'Status keputusan approval harus Approve atau Reject.',
            'notes.required' => 'Catatan penolakan wajib diisi jika approval ditolak.',
            'notes.max' => 'Catatan tidak boleh melebihi 1000 karakter.',
        ];
    }
}
