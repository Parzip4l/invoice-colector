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

        // Reorder memo, tanda terima, dan dokumen pendukung
        DB::table('document_types')
            ->where('transaction_type_id', $transactionTypeId)
            ->where('code', 'PNK_MEMO_PERMOHONAN')
            ->update(['sort_order' => 4, 'updated_at' => now()]);

        DB::table('document_types')
            ->where('transaction_type_id', $transactionTypeId)
            ->where('code', 'PNK_LEMBAR_TANDA_TERIMA')
            ->update(['sort_order' => 6, 'updated_at' => now()]);

        DB::table('document_types')
            ->where('transaction_type_id', $transactionTypeId)
            ->where('code', 'PNK_DOKUMEN_PENDUKUNG')
            ->update(['sort_order' => 7, 'updated_at' => now()]);

        // Faktur Pajak
        $taxDoc = DB::table('document_types')
            ->where('code', 'PNK_FAKTUR_PAJAK')
            ->where('transaction_type_id', $transactionTypeId);

        if ($taxDoc->exists()) {
            $taxDoc->update([
                'name' => 'Faktur Pajak',
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
                'code' => 'PNK_FAKTUR_PAJAK',
                'name' => 'Faktur Pajak',
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

        // Laporan Pekerjaan
        $workReportDoc = DB::table('document_types')
            ->where('code', 'PNK_LAPORAN_PEKERJAAN')
            ->where('transaction_type_id', $transactionTypeId);

        if ($workReportDoc->exists()) {
            $workReportDoc->update([
                'name' => 'Laporan Pekerjaan',
                'source_type' => 'VENDOR',
                'upload_mode' => 'SINGLE_SLOT',
                'approval_mode' => 'NONE',
                'is_required' => true,
                'sort_order' => 5,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('document_types')->insert([
                'id' => (string) Str::ulid(),
                'code' => 'PNK_LAPORAN_PEKERJAAN',
                'name' => 'Laporan Pekerjaan',
                'transaction_type_id' => $transactionTypeId,
                'source_type' => 'VENDOR',
                'upload_mode' => 'SINGLE_SLOT',
                'approval_mode' => 'NONE',
                'is_required' => true,
                'sort_order' => 5,
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
            ->whereIn('code', ['PNK_FAKTUR_PAJAK', 'PNK_LAPORAN_PEKERJAAN'])
            ->delete();

        DB::table('document_types')
            ->where('transaction_type_id', $transactionTypeId)
            ->where('code', 'PNK_MEMO_PERMOHONAN')
            ->update(['sort_order' => 3, 'updated_at' => now()]);

        DB::table('document_types')
            ->where('transaction_type_id', $transactionTypeId)
            ->where('code', 'PNK_LEMBAR_TANDA_TERIMA')
            ->update(['sort_order' => 4, 'updated_at' => now()]);

        DB::table('document_types')
            ->where('transaction_type_id', $transactionTypeId)
            ->where('code', 'PNK_DOKUMEN_PENDUKUNG')
            ->update(['sort_order' => 5, 'updated_at' => now()]);
    }
};
