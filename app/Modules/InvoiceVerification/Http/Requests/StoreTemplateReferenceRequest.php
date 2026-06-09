<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageMasterData', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('template_references', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'template_type' => ['required', 'in:GENERATED_DOCUMENT,FINAL_COMPILATION_ORDER'],
            'transaction_type_id' => ['nullable', 'exists:transaction_types,id'],
            'document_code' => ['nullable', 'string', 'max:255'],
            'file_path' => ['nullable', 'string', 'max:255'],
            'configuration_json' => ['nullable', 'array'],
        ];
    }
}
