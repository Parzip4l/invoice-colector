<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_types')
            ->where('code', 'PPA_LAPORAN_PEKERJAAN')
            ->update([
                'is_required' => true,
                'sort_order' => 10,
            ]);

        DB::table('document_types')
            ->where('code', 'PPA_LAMPIRAN_PEKERJAAN')
            ->update([
                'is_required' => false,
                'sort_order' => 11,
            ]);
    }

    public function down(): void
    {
        DB::table('document_types')
            ->where('code', 'PPA_LAPORAN_PEKERJAAN')
            ->update([
                'is_required' => false,
                'sort_order' => 11,
            ]);

        DB::table('document_types')
            ->where('code', 'PPA_LAMPIRAN_PEKERJAAN')
            ->update([
                'is_required' => false,
                'sort_order' => 10,
            ]);
    }
};
