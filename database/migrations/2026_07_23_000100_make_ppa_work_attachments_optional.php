<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_types')
            ->whereIn('code', ['PPA_LAMPIRAN_PEKERJAAN', 'PPA_LAPORAN_PEKERJAAN'])
            ->update(['is_required' => false]);
    }

    public function down(): void
    {
        DB::table('document_types')
            ->whereIn('code', ['PPA_LAMPIRAN_PEKERJAAN', 'PPA_LAPORAN_PEKERJAAN'])
            ->update(['is_required' => true]);
    }
};
