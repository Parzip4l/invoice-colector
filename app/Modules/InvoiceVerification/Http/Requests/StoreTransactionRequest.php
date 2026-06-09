<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'division_id' => $this->user()?->division_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'transaction_type_id' => ['required', 'exists:transaction_types,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'division_id' => ['required', 'exists:divisions,id'],
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('division_id', $this->input('division_id'))),
            ],
            'memo_request_id' => [
                'required',
                Rule::exists('memo_requests', 'id')->where(function ($query) {
                    return $query
                        ->where('division_id', $this->input('division_id'))
                        ->where('department_id', $this->input('department_id'));
                }),
            ],
            'agreement_reference_id' => [
                'required',
                Rule::exists('agreement_references', 'id')->where(function ($query) {
                    return $query
                        ->where('division_id', $this->input('division_id'))
                        ->where('department_id', $this->input('department_id'))
                        ->where('vendor_id', $this->input('vendor_id'));
                }),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
