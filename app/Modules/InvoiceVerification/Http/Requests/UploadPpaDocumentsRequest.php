<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadPpaDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $this->user()?->can('uploadDocuments', $transaction) ?? false;
    }

    public function rules(): array
    {
        $mimes = implode(',', config('invoice_verification.storage.allowed_mimes'));
        $maxKb = (int) config('invoice_verification.storage.max_upload_kb');

        return [
            'invoice_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('invoice_metadata', 'invoice_number')
                    ->ignore($this->route('transaction')?->invoiceMetadata?->id, 'id')
                    ->where(fn ($query) => $query->where('vendor_id', $this->route('transaction')?->vendor_id)),
            ],
            'invoice_date' => ['required', 'date'],
            'received_date' => ['nullable', 'date'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'invoice_value' => ['required', 'numeric', 'min:0'],
            'ppn_value' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'documents' => ['required', 'array', 'min:1'],
            'documents.*.document_type_id' => ['required', 'exists:document_types,id'],
            'documents.*.file' => ['required', 'file', 'mimes:'.$mimes, 'max:'.$maxKb],
            'documents.*.document_information.document_number' => ['required', 'string', 'max:255'],
            'documents.*.document_information.document_date' => ['required', 'date'],
            'documents.*.document_information.notes' => ['nullable', 'string'],
        ];
    }
}
