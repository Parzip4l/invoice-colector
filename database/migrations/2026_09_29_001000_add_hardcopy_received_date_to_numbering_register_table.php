<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('numbering_register', function (Blueprint $table) {
            $table->date('hardcopy_received_date')->nullable()->after('hardcopy_received');
            $table->index('hardcopy_received_date');
        });
    }

    public function down(): void
    {
        Schema::table('numbering_register', function (Blueprint $table) {
            $table->dropIndex(['hardcopy_received_date']);
            $table->dropColumn('hardcopy_received_date');
        });
    }
};
