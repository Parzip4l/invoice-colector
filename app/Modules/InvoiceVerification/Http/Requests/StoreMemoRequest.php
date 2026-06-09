<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createMemoRequest', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'memo_number' => ['required', 'string', 'max:255', Rule::unique('memo_requests', 'memo_number')],
            'memo_date' => ['required', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'division_id' => ['required', 'exists:divisions,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'memo_file' => [
                'required',
                'file',
                'mimes:'.implode(',', config('invoice_verification.storage.allowed_mimes', ['pdf'])),
                'max:'.config('invoice_verification.storage.max_upload_kb', 10240),
            ],
        ];
    }
}
