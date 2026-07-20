<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchedulePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('processFinance', $this->route('transaction')) ?? false;
    }

    public function rules(): array
    {
        return [
            'scheduled_payment_at' => ['required', 'date'],
        ];
    }
}
