<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UploadCombinedDocumentsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $attachments = collect((array) $this->input('attachments', []))
            ->filter(function ($item, $key) {
                $hasInformation = collect((array) data_get($item, 'document_information'))
                    ->contains(fn ($value) => $value !== null && $value !== '');

                return $this->hasFile("attachments.$key.file") || $hasInformation;
            })
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
            'attachments.*.file' => ['nullable', 'file', 'mimes:'.$mimes, 'max:'.$maxKb],
            'attachments.*.document_information' => ['nullable', 'array'],
            'attachments.*.document_information.document_number' => ['nullable', 'string', 'max:255'],
            'attachments.*.document_information.document_date' => ['nullable', 'date'],
            'attachments.*.document_information.dpp_value' => ['nullable', 'numeric', 'min:0'],
            'attachments.*.document_information.ppn_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.*.document_information.document_number.required' => 'Nomor dokumen wajib diisi.',
            'attachments.*.document_information.document_date.required' => 'Tanggal dokumen wajib diisi.',
            'attachments.*.document_information.document_date.date' => 'Format tanggal dokumen tidak valid.',
            'attachments.*.document_information.dpp_value.required' => 'Nominal DPP wajib diisi.',
            'attachments.*.document_information.dpp_value.numeric' => 'Nominal DPP harus berupa angka.',
            'attachments.*.document_information.ppn_value.required' => 'Nominal PPN wajib diisi.',
            'attachments.*.document_information.ppn_value.numeric' => 'Nominal PPN harus berupa angka.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $attachments = collect((array) $this->input('attachments', []));
            $types = DocumentType::query()
                ->whereIn('id', $attachments->pluck('document_type_id')->filter())
                ->pluck('code', 'id');

            foreach ($attachments as $index => $attachment) {
                $code = $types->get($attachment['document_type_id'] ?? null);
                $hasExistingDocument = $this->route('transaction')?->latestDocuments()
                    ->where('document_type_id', $attachment['document_type_id'] ?? null)
                    ->exists() ?? false;

                if (! $this->hasFile("attachments.{$index}.file") && ! $hasExistingDocument) {
                    $validator->errors()->add("attachments.{$index}.file", 'File dokumen wajib dipilih.');
                }

                if (! in_array($code, ['PNK_INVOICE', 'PNK_KWITANSI', 'PNK_FAKTUR_PAJAK', 'PNK_MEMO_PERMOHONAN'], true)) {
                    continue;
                }

                if (blank(data_get($attachment, 'document_information.document_number'))) {
                    $validator->errors()->add(
                        "attachments.{$index}.document_information.document_number",
                        match ($code) {
                            'PNK_INVOICE' => 'Nomor invoice wajib diisi.',
                            'PNK_KWITANSI' => 'Nomor kwitansi wajib diisi.',
                            'PNK_FAKTUR_PAJAK' => 'Nomor faktur pajak wajib diisi.',
                            default => 'Nomor memo wajib diisi.',
                        },
                    );
                }

                if (in_array($code, ['PNK_INVOICE', 'PNK_KWITANSI', 'PNK_FAKTUR_PAJAK'], true)) {
                    if (blank(data_get($attachment, 'document_information.document_date'))) {
                        $validator->errors()->add(
                            "attachments.{$index}.document_information.document_date",
                            match ($code) {
                                'PNK_INVOICE' => 'Tanggal invoice wajib diisi.',
                                'PNK_KWITANSI' => 'Tanggal kwitansi wajib diisi.',
                                'PNK_FAKTUR_PAJAK' => 'Tanggal faktur pajak wajib diisi.',
                            },
                        );
                    }
                }

                if ($code === 'PNK_INVOICE') {
                    foreach (['dpp_value' => 'Nominal DPP wajib diisi.', 'ppn_value' => 'Nominal PPN wajib diisi.'] as $field => $message) {
                        if (data_get($attachment, "document_information.{$field}") === null || data_get($attachment, "document_information.{$field}") === '') {
                            $validator->errors()->add("attachments.{$index}.document_information.{$field}", $message);
                        }
                    }
                }
            }
        });
    }
}
