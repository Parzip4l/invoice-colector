<?php

namespace App\Modules\InvoiceVerification\Http\Requests;

use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $payload = ['division_id' => $this->user()?->division_id];

        if ($this->filled('transaction_account_number')) {
            $payload['transaction_account_number'] = preg_replace('/[\s-]+/', '', (string) $this->input('transaction_account_number'));
        }

        $this->merge($payload);
    }

    public function rules(): array
    {
        return [
            'transaction_type_id' => ['required', 'exists:transaction_types,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'division_id' => ['required', 'exists:divisions,id'],
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('division_id', $this->input('division_id'))),
            ],
            'memo_request_id' => [
                'required',
                Rule::exists('memo_requests', 'id')->where(function ($query) {
                    return $query
                        ->where('division_id', $this->input('division_id'))
                        ->where('department_id', $this->input('department_id'));
                }),
            ],
            'agreement_reference_id' => [
                'nullable',
                Rule::exists('agreement_references', 'id')->where(function ($query) {
                    return $query
                        ->where('division_id', $this->input('division_id'))
                        ->where('department_id', $this->input('department_id'))
                        ->when($this->input('vendor_id'), fn ($vendorQuery) => $vendorQuery->where('vendor_id', $this->input('vendor_id')));
                }),
            ],
            'parent_spu_transaction_id' => ['nullable', 'exists:transactions,id'],
            'activity_name' => ['nullable', 'string', 'max:255'],
            'transaction_bank_name' => ['nullable', 'string', 'max:255'],
            'transaction_account_number' => ['nullable', 'regex:/^\d{6,30}$/'],
            'spu_amount' => ['nullable', 'numeric', 'min:0'],
            'accountability_amount' => ['nullable', 'numeric'],
            'petty_cash_remaining_amount' => ['nullable', 'numeric'],
            'period' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_account_number.regex' => 'Nomor rekening hanya boleh berisi angka, 6 sampai 30 digit.',
            'spu_amount.numeric' => 'Nilai SPU harus berupa angka.',
            'accountability_amount.numeric' => 'Nilai pertanggungjawaban harus berupa angka.',
            'petty_cash_remaining_amount.numeric' => 'Nilai sisa kas kecil harus berupa angka.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = TransactionType::query()->find($this->input('transaction_type_id'));
            $user = $this->user();

            if (! $type || ! $user) {
                return;
            }

            $code = $type->code;

            if ($user->hasRole(RoleCode::VENDOR) && $code !== TransactionTypeCode::PPA) {
                $validator->errors()->add('transaction_type_id', 'Jenis transaksi ini tidak dapat dibuat oleh Vendor Eksternal.');
            }

            if ($user->hasRole(RoleCode::USER_DIVISI) && ! $code->isInternalVendorType()) {
                $validator->errors()->add('transaction_type_id', 'Vendor Internal tidak dapat membuat PPA Kontrak.');
            }

            if ($code === TransactionTypeCode::PPA && blank($this->input('agreement_reference_id'))) {
                $validator->errors()->add('agreement_reference_id', 'Kontrak wajib dipilih untuk PPA Kontrak.');
            }

            if (in_array($code, [TransactionTypeCode::PPA_NON_CONTRACT, TransactionTypeCode::SPU], true)) {
                foreach (['activity_name' => 'Nama kegiatan wajib diisi.', 'transaction_bank_name' => 'Nama bank wajib diisi.', 'transaction_account_number' => 'Nomor rekening wajib diisi.'] as $field => $message) {
                    if (blank($this->input($field))) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }

            if ($code === TransactionTypeCode::SPU && blank($this->input('spu_amount'))) {
                $validator->errors()->add('spu_amount', 'Nilai SPU wajib diisi.');
            }

            if ($code === TransactionTypeCode::SPUK) {
                $parent = Transaction::query()->with('transactionType')->find($this->input('parent_spu_transaction_id'));

                if (! $parent || ! $parent->isSpu() || $parent->owner_user_id !== $user->id) {
                    $validator->errors()->add('parent_spu_transaction_id', 'Transaksi SPU yang dipilih tidak valid.');
                }

                if (blank($this->input('accountability_amount'))) {
                    $validator->errors()->add('accountability_amount', 'Nilai pertanggungjawaban wajib diisi.');
                }
            }

            if ($code === TransactionTypeCode::KAS_KECIL) {
                if (blank($this->input('period'))) {
                    $validator->errors()->add('period', 'Periode wajib diisi.');
                }

                if (blank($this->input('petty_cash_remaining_amount'))) {
                    $validator->errors()->add('petty_cash_remaining_amount', 'Nilai sisa kas kecil wajib diisi.');
                }

                if ($user->division?->petty_cash_ceiling === null) {
                    $validator->errors()->add('petty_cash_remaining_amount', 'Plafon Kas Kecil untuk divisi Anda belum dikonfigurasi.');
                }
            }
        });
    }
}
