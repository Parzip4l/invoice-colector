<?php

namespace Database\Seeders\InvoiceVerification;

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
            TransactionTypeCode::PPA->value => [],
            TransactionTypeCode::SPU->value => [],
            TransactionTypeCode::SPUK->value => [],
            TransactionTypeCode::KAS_KECIL->value => [],
            TransactionTypeCode::PPA_NON_CONTRACT->value => [
                [
                    'document_code' => null,
                    'step_no' => 1,
                    'step_code' => \App\Modules\InvoiceVerification\Domain\Enums\RoleCode::KEPALA_DEPARTEMEN->value,
                    'step_name' => 'Kepala Departemen',
                ],
                [
                    'document_code' => null,
                    'step_no' => 2,
                    'step_code' => \App\Modules\InvoiceVerification\Domain\Enums\RoleCode::KEPALA_DIVISI->value,
                    'step_name' => 'Kepala Divisi',
                ],
                [
                    'document_code' => null,
                    'step_no' => 3,
                    'step_code' => \App\Modules\InvoiceVerification\Domain\Enums\RoleCode::KEPALA_DIVISI->value,
                    'step_name' => 'Kepala Divisi Keuangan dan Akuntansi',
                ],
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
