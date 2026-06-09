<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgreementReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createAgreementReference', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'division_id' => $this->user()?->division_id,
            'department_id' => $this->user()?->department_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'division_id' => ['required', 'exists:divisions,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'contract_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('agreement_references', 'contract_number')->where(function ($query) {
                    return $query
                        ->where('division_id', $this->input('division_id'))
                        ->where('department_id', $this->input('department_id'));
                }),
            ],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'effective_date' => ['nullable', 'date'],
            'expired_at' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'agreement_file' => [
                'required',
                'file',
                'mimes:'.implode(',', config('invoice_verification.storage.allowed_mimes', ['pdf'])),
                'max:'.config('invoice_verification.storage.max_upload_kb', 10240),
            ],
        ];
    }
}
