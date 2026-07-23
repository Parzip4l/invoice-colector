<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UploadPpaDocumentsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('account_number')) {
            $this->merge(['account_number' => preg_replace('/[\s-]+/', '', (string) $this->input('account_number'))]);
        }

        $invoiceDocument = $this->findSubmittedDocumentByCode('PPA_INVOICE');
        $invoiceNumber = $this->input('invoice_number')
            ?: data_get($invoiceDocument, 'document_information.document_number')
            ?: $this->route('transaction')?->invoiceMetadata?->invoice_number
            ?: $this->route('transaction')?->registration_number;
        $invoiceDate = $this->input('invoice_date')
            ?: data_get($invoiceDocument, 'document_information.document_date')
            ?: $this->route('transaction')?->invoiceMetadata?->invoice_date?->format('Y-m-d');
        $invoiceValue = $this->input('invoice_value')
            ?: data_get($invoiceDocument, 'document_information.invoice_value')
            ?: $this->route('transaction')?->invoiceMetadata?->invoice_value;
        $ppnValue = $this->input('ppn_value')
            ?: data_get($invoiceDocument, 'document_information.ppn_value')
            ?: $this->route('transaction')?->invoiceMetadata?->ppn_value;

        $this->merge([
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,
            'invoice_value' => $invoiceValue,
            'ppn_value' => $ppnValue,
        ]);
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
            'invoice_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('invoice_metadata', 'invoice_number')
                    ->ignore($this->route('transaction')?->invoiceMetadata?->id, 'id')
                    ->where(fn ($query) => $query->where('vendor_id', $this->route('transaction')?->vendor_id)),
            ],
            'invoice_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
            'account_number' => ['nullable', 'regex:/^\d{6,30}$/'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'invoice_value' => ['nullable', 'numeric', 'min:0'],
            'ppn_value' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'documents' => ['required', 'array', 'min:1'],
            'documents.*.document_type_id' => ['required', 'exists:document_types,id'],
            'documents.*.file' => ['required', 'file', 'mimes:'.$mimes, 'max:'.$maxKb],
            'documents.*.document_information.document_number' => ['required', 'string', 'max:255'],
            'documents.*.document_information.document_date' => ['required', 'date'],
            'documents.*.document_information.invoice_value' => ['nullable', 'numeric', 'min:0'],
            'documents.*.document_information.ppn_value' => ['nullable', 'numeric', 'min:0'],
            'documents.*.document_information.notes' => ['nullable', 'string'],
        ];
    }

    private function findSubmittedDocumentByCode(string $code): ?array
    {
        $submittedDocuments = collect($this->input('documents', []));

        if ($submittedDocuments->isEmpty()) {
            return null;
        }

        $documentTypeIds = $submittedDocuments
            ->pluck('document_type_id')
            ->filter()
            ->values();

        if ($documentTypeIds->isEmpty()) {
            return null;
        }

        $documentTypes = DocumentType::query()
            ->whereIn('id', $documentTypeIds)
            ->pluck('code', 'id');

        foreach ($submittedDocuments as $document) {
            $documentCode = (string) ($documentTypes[$document['document_type_id'] ?? null] ?? '');

            if ($documentCode === $code) {
                return $document;
            }
        }

        return null;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $submittedDocuments = collect($this->input('documents', []));

            if ($submittedDocuments->isEmpty()) {
                return;
            }

            $documentTypeIds = $submittedDocuments
                ->pluck('document_type_id')
                ->filter()
                ->values();

            if ($documentTypeIds->isEmpty()) {
                return;
            }

            $documentTypes = DocumentType::query()
                ->whereIn('id', $documentTypeIds)
                ->pluck('code', 'id');

            $requiredDateCodes = [
                'PPA_INVOICE' => 'Invoice',
                'PPA_FAKTUR_PAJAK' => 'Faktur Pajak',
                'PPA_KWITANSI' => 'Kwitansi',
            ];
            $dates = [];

            foreach ($submittedDocuments as $index => $document) {
                $code = (string) ($documentTypes[$document['document_type_id'] ?? null] ?? '');

                if ($code === 'PPA_INVOICE' && blank(data_get($document, 'document_information.invoice_value'))) {
                    $validator->errors()->add(
                        "documents.{$index}.document_information.invoice_value",
                        'Nilai Invoice wajib diisi pada dokumen Invoice.',
                    );
                }

                if (! array_key_exists($code, $requiredDateCodes)) {
                    continue;
                }

                $date = data_get($document, 'document_information.document_date');

                if ($date) {
                    $dates[$code] = [
                        'index' => $index,
                        'date' => $date,
                        'label' => $requiredDateCodes[$code],
                    ];
                }
            }

            if (count($dates) < count($requiredDateCodes) || count(array_unique(array_column($dates, 'date'))) <= 1) {
                return;
            }

            foreach ($dates as $item) {
                $validator->errors()->add(
                    "documents.{$item['index']}.document_information.document_date",
                    'Tanggal Invoice, Faktur Pajak, dan Kwitansi harus sama.',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'account_number.regex' => 'Nomor rekening hanya boleh berisi angka, 6 sampai 30 digit.',
            'invoice_value.numeric' => 'Nilai invoice harus berupa angka.',
            'invoice_value.min' => 'Nilai invoice tidak boleh kurang dari 0.',
        ];
    }
}
