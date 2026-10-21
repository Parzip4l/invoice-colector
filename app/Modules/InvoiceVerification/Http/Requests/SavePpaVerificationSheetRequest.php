<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SavePpaVerificationSheetRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('budget_items') && is_array($this->input('budget_items'))) {
            $budgetItems = collect($this->input('budget_items'))
                ->map(function ($item) {
                    if (isset($item['nilai_realisasi']) && is_string($item['nilai_realisasi'])) {
                        $clean = preg_replace('/[^\d]/', '', $item['nilai_realisasi']);
                        $item['nilai_realisasi'] = $clean !== '' ? (float) $clean : 0;
                    }

                    return $item;
                })
                ->all();

            $this->merge(['budget_items' => $budgetItems]);
        }

        if ($this->has('advance_payment_value') && is_string($this->input('advance_payment_value'))) {
            $clean = preg_replace('/[^\d]/', '', (string) $this->input('advance_payment_value'));
            $this->merge(['advance_payment_value' => $clean !== '' ? (float) $clean : 0]);
        }
    }

    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        if ($transaction?->isPpaNonContract()) {
            return $this->user()?->can('update', $transaction) ?? false;
        }

        return $this->user()?->can('update', $transaction->ppaVerificationSheet ?? PpaVerificationSheet::class) ?? false;
    }

    public function rules(): array
    {
        $transaction = $this->route('transaction');

        if ($transaction?->isPpaNonContract()) {
            return [
                'description' => ['nullable', 'string', 'max:1000'],
                'advance_payment_value' => ['nullable', 'numeric', 'min:0'],
                'exchange_rate_value' => ['nullable', 'numeric', 'min:0'],
                'budget_items' => ['required', 'array', 'min:1'],
                'budget_items.*.kode_rab' => ['required', 'string', 'max:255'],
                'budget_items.*.nama_kegiatan' => ['required', 'string', 'max:255'],
                'budget_items.*.nilai_realisasi' => ['required', 'numeric', 'min:0'],
                'checklist_items' => ['nullable', 'array'],
                'checklist_items.*.is_required' => ['nullable', 'boolean'],
                'checklist_items.*.is_actual' => ['nullable', 'boolean'],
                'checklist_items.*.notes' => ['nullable', 'string', 'max:255'],
                'action' => ['nullable', 'string', 'in:save,submit'],
            ];
        }

        return [
            'items' => ['required', 'array'],
            'items.*.document_type_id' => ['required', 'exists:document_types,id'],
            'items.*.attachment_status' => ['required', 'in:ATTACHED,NOT_ATTACHED'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'budget_items.required' => 'Rincian RAB minimal harus memiliki 1 baris kegiatan.',
            'budget_items.min' => 'Rincian RAB minimal harus memiliki 1 baris kegiatan.',
            'budget_items.*.kode_rab.required' => 'Kode RAB wajib diisi.',
            'budget_items.*.nama_kegiatan.required' => 'Nama kegiatan wajib diisi.',
            'budget_items.*.nilai_realisasi.required' => 'Nilai realisasi wajib diisi.',
            'budget_items.*.nilai_realisasi.numeric' => 'Nilai realisasi harus berupa angka.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $transaction = $this->route('transaction');

            if (! $transaction?->isPpaNonContract()) {
                $documentTypeIds = collect($this->input('items', []))->pluck('document_type_id');

                if ($documentTypeIds->duplicates()->isNotEmpty()) {
                    $validator->errors()->add('items', 'Checklist PPA tidak boleh memiliki item dokumen yang sama lebih dari sekali.');
                }
            }
        });
    }
}
