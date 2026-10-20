<?php

use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $transactionTypeId = DB::table('transaction_types')
            ->where('code', TransactionTypeCode::PPA_NON_CONTRACT->value)
            ->value('id');

        if (! $transactionTypeId) {
            return;
        }

        $flows = [
            [
                'step_no' => 1,
                'step_code' => RoleCode::KEPALA_DEPARTEMEN->value,
                'step_name' => 'Kepala Departemen',
                'is_required' => true,
            ],
            [
                'step_no' => 2,
                'step_code' => RoleCode::KEPALA_DIVISI->value,
                'step_name' => 'Kepala Divisi',
                'is_required' => true,
            ],
        ];

        foreach ($flows as $flow) {
            $existing = DB::table('approval_flows')
                ->where('transaction_type_id', $transactionTypeId)
                ->whereNull('document_code')
                ->where('step_no', $flow['step_no'])
                ->first();

            if ($existing) {
                DB::table('approval_flows')
                    ->where('id', $existing->id)
                    ->update([
                        'step_code' => $flow['step_code'],
                        'step_name' => $flow['step_name'],
                        'is_required' => true,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('approval_flows')->insert([
                    'id' => (string) Str::ulid(),
                    'transaction_type_id' => $transactionTypeId,
                    'document_code' => null,
                    'step_no' => $flow['step_no'],
                    'step_code' => $flow['step_code'],
                    'step_name' => $flow['step_name'],
                    'is_required' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $transactionTypeId = DB::table('transaction_types')
            ->where('code', TransactionTypeCode::PPA_NON_CONTRACT->value)
            ->value('id');

        if (! $transactionTypeId) {
            return;
        }

        DB::table('approval_flows')
            ->where('transaction_type_id', $transactionTypeId)
            ->whereNull('document_code')
            ->delete();
    }
};
