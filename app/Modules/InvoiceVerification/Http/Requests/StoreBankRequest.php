<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageMasterData', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('banks', 'code')],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
