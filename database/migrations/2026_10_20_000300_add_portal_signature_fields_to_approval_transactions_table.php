<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_transactions', function (Blueprint $table) {
            $table->string('portal_signature_id')->nullable()->after('notes');
            $table->string('portal_signature_url', 2048)->nullable()->after('portal_signature_id');
            $table->string('portal_qr_url', 2048)->nullable()->after('portal_signature_url');
        });
    }

    public function down(): void
    {
        Schema::table('approval_transactions', function (Blueprint $table) {
            $table->dropColumn(['portal_signature_id', 'portal_signature_url', 'portal_qr_url']);
        });
    }
};
