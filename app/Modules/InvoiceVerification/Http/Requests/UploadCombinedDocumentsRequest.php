<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCombinedDocumentsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $attachments = collect((array) $this->input('attachments', []))
            ->filter(fn ($item, $key) => $this->hasFile("attachments.$key.file"))
            ->values()
            ->all();

        $this->merge(['attachments' => $attachments]);
    }

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
            'attachments' => ['required', 'array', 'min:1'],
            'attachments.*.document_type_id' => ['required', 'exists:document_types,id'],
            'attachments.*.source_actor' => ['required', 'in:USER_DIVISI,VENDOR'],
            'attachments.*.document_label' => ['required', 'string', 'max:255'],
            'attachments.*.file' => ['required', 'file', 'mimes:'.$mimes, 'max:'.$maxKb],
        ];
    }
}
