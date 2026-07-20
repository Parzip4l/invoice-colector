<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('default_account_number')) {
            $this->merge(['default_account_number' => preg_replace('/[\s-]+/', '', (string) $this->input('default_account_number'))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manageMasterData', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'vendor_password' => ['nullable', 'required_with:contact_email', 'string', 'min:8'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'default_account_number' => ['nullable', 'regex:/^\d{6,30}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'default_account_number.regex' => 'Nomor rekening hanya boleh berisi angka, 6 sampai 30 digit.',
        ];
    }
}
