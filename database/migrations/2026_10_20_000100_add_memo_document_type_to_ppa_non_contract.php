<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $transactionTypeId = DB::table('transaction_types')
            ->where('code', 'PPA_NON_CONTRACT')
            ->value('id');

        if (! $transactionTypeId) {
            return;
        }

        DB::table('document_types')
            ->where('transaction_type_id', $transactionTypeId)
            ->where('code', 'PNK_LEMBAR_TANDA_TERIMA')
            ->update(['sort_order' => 4, 'updated_at' => now()]);

        DB::table('document_types')
            ->where('transaction_type_id', $transactionTypeId)
            ->where('code', 'PNK_DOKUMEN_PENDUKUNG')
            ->update(['sort_order' => 5, 'updated_at' => now()]);

        $memoDocument = DB::table('document_types')
            ->where('code', 'PNK_MEMO_PERMOHONAN')
            ->where('transaction_type_id', $transactionTypeId);

        if ($memoDocument->exists()) {
            $memoDocument->update([
                'name' => 'Memo Permohonan',
                'source_type' => 'VENDOR',
                'upload_mode' => 'SINGLE_SLOT',
                'approval_mode' => 'NONE',
                'is_required' => true,
                'sort_order' => 3,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('document_types')->insert([
                'id' => (string) Str::ulid(),
                'code' => 'PNK_MEMO_PERMOHONAN',
                'name' => 'Memo Permohonan',
                'transaction_type_id' => $transactionTypeId,
                'source_type' => 'VENDOR',
                'upload_mode' => 'SINGLE_SLOT',
                'approval_mode' => 'NONE',
                'is_required' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $transactionTypeId = DB::table('transaction_types')
            ->where('code', 'PPA_NON_CONTRACT')
            ->value('id');

        if (! $transactionTypeId) {
            return;
        }

        DB::table('document_types')
            ->where('transaction_type_id', $transactionTypeId)
            ->where('code', 'PNK_MEMO_PERMOHONAN')
            ->delete();

        DB::table('document_types')
            ->where('transaction_type_id', $transactionTypeId)
            ->where('code', 'PNK_LEMBAR_TANDA_TERIMA')
            ->update(['sort_order' => 3, 'updated_at' => now()]);

        DB::table('document_types')
            ->where('transaction_type_id', $transactionTypeId)
            ->where('code', 'PNK_DOKUMEN_PENDUKUNG')
            ->update(['sort_order' => 4, 'updated_at' => now()]);
    }
};
