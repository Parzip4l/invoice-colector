<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('approval_flows')) {
            DB::table('approval_flows')
                ->whereIn('step_code', ['KEPALA_DEPARTEMEN', 'KEPALA_DIVISI'])
                ->update(['is_required' => false]);
        }

        if (Schema::hasTable('approval_transactions') && Schema::hasTable('approval_flows')) {
            DB::table('approval_transactions')
                ->where('status', 'PENDING')
                ->whereIn('approval_flow_id', function ($query) {
                    $query->select('id')
                        ->from('approval_flows')
                        ->whereIn('step_code', ['KEPALA_DEPARTEMEN', 'KEPALA_DIVISI']);
                })
                ->update([
                    'status' => 'REJECTED',
                    'notes' => 'Approval Kadep/Kadiv dinonaktifkan dari workflow aktif.',
                    'action_at' => now(),
                ]);
        }

        if (Schema::hasTable('transactions')) {
            DB::table('transactions')
                ->where('status', 'WAITING_APPROVAL')
                ->whereIn('current_step', ['KADEP_REVIEW', 'KADIV_REVIEW'])
                ->update([
                    'status' => 'ADMIN_GENERATE_DOCUMENTS',
                    'current_step' => 'INITIAL_DOCUMENT_GENERATION',
                    'updated_at' => now(),
                ]);

            DB::table('transactions')
                ->where('status', 'WAITING_APPROVAL')
                ->where('current_step', 'INITIAL_APPROVAL')
                ->update([
                    'status' => 'DOCUMENT_COLLECTION',
                    'current_step' => 'INTERNAL_DOCUMENT_UPLOAD',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Business workflow removal is intentionally not restored automatically.
    }
};
