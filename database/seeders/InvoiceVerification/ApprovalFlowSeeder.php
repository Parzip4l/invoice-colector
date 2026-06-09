<?php

namespace Database\Seeders\InvoiceVerification;

use App\Modules\InvoiceVerification\Domain\Enums\DocumentCode;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalFlow;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use Illuminate\Database\Seeder;

class ApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        $transactionTypes = TransactionType::query()->get()->keyBy(fn (TransactionType $type) => $type->code->value);
        ApprovalFlow::query()
            ->whereIn('transaction_type_id', $transactionTypes->pluck('id'))
            ->update(['is_required' => false]);

        $flows = [
            TransactionTypeCode::PPA->value => [
                ['document_code' => DocumentCode::PPA_LEMBAR_AWAL->value, 'step_no' => 1, 'step_code' => RoleCode::KEPALA_DEPARTEMEN->value, 'step_name' => 'Kepala Departemen'],
                ['document_code' => DocumentCode::PPA_LEMBAR_AWAL->value, 'step_no' => 2, 'step_code' => RoleCode::KEPALA_DIVISI->value, 'step_name' => 'Kepala Divisi'],
                ['document_code' => DocumentCode::PPA_LEMBAR_VERIFIKASI->value, 'step_no' => 1, 'step_code' => RoleCode::KEPALA_DEPARTEMEN->value, 'step_name' => 'Kepala Departemen'],
                ['document_code' => DocumentCode::PPA_LEMBAR_VERIFIKASI->value, 'step_no' => 2, 'step_code' => RoleCode::KEPALA_DIVISI->value, 'step_name' => 'Kepala Divisi'],
            ],
            TransactionTypeCode::SPU->value => [
                ['document_code' => DocumentCode::SPU_INITIAL_FORM->value, 'step_no' => 1, 'step_code' => RoleCode::ADMIN_DIVISI->value, 'step_name' => 'Admin Divisi'],
                ['document_code' => DocumentCode::SPU_INITIAL_FORM->value, 'step_no' => 2, 'step_code' => RoleCode::KEPALA_DEPARTEMEN->value, 'step_name' => 'Kepala Departemen'],
                ['document_code' => DocumentCode::SPU_INITIAL_FORM->value, 'step_no' => 3, 'step_code' => RoleCode::KEPALA_DIVISI->value, 'step_name' => 'Kepala Divisi'],
            ],
            TransactionTypeCode::SPUK->value => [
                ['document_code' => DocumentCode::SPUK_INITIAL_FORM->value, 'step_no' => 1, 'step_code' => RoleCode::ADMIN_DIVISI->value, 'step_name' => 'Admin Divisi'],
                ['document_code' => DocumentCode::SPUK_INITIAL_FORM->value, 'step_no' => 2, 'step_code' => RoleCode::KEPALA_DEPARTEMEN->value, 'step_name' => 'Kepala Departemen'],
                ['document_code' => DocumentCode::SPUK_INITIAL_FORM->value, 'step_no' => 3, 'step_code' => RoleCode::KEPALA_DIVISI->value, 'step_name' => 'Kepala Divisi'],
            ],
            TransactionTypeCode::KAS_KECIL->value => [
                ['document_code' => DocumentCode::KAS_KECIL_INITIAL_FORM->value, 'step_no' => 1, 'step_code' => RoleCode::ADMIN_DIVISI->value, 'step_name' => 'Admin Divisi'],
                ['document_code' => DocumentCode::KAS_KECIL_INITIAL_FORM->value, 'step_no' => 2, 'step_code' => RoleCode::KEPALA_DEPARTEMEN->value, 'step_name' => 'Kepala Departemen'],
                ['document_code' => DocumentCode::KAS_KECIL_INITIAL_FORM->value, 'step_no' => 3, 'step_code' => RoleCode::KEPALA_DIVISI->value, 'step_name' => 'Kepala Divisi'],
            ],
        ];

        foreach ($flows as $transactionTypeCode => $items) {
            foreach ($items as $item) {
                ApprovalFlow::updateOrCreate(
                    [
                        'transaction_type_id' => $transactionTypes[$transactionTypeCode]->id,
                        'document_code' => $item['document_code'],
                        'step_no' => $item['step_no'],
                    ],
                    [
                        'step_code' => $item['step_code'],
                        'step_name' => $item['step_name'],
                        'is_required' => true,
                    ],
                );
            }
        }
    }
}
