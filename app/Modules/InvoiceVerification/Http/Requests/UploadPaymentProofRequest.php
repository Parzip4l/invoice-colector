<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('processFinance', $this->route('transaction')) ?? false;
    }

    public function rules(): array
    {
        $mimes = implode(',', config('invoice_verification.storage.allowed_mimes'));
        $maxKb = (int) config('invoice_verification.storage.max_upload_kb');

        return [
            'payment_proof' => ['required', 'file', 'mimes:'.$mimes, 'max:'.$maxKb],
        ];
    }
}
