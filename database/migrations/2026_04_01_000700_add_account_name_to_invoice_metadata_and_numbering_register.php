<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_metadata', function (Blueprint $table) {
            $table->string('account_name')->nullable()->after('account_number');
        });

        Schema::table('numbering_register', function (Blueprint $table) {
            $table->string('account_name')->nullable()->after('account_number');
        });
    }

    public function down(): void
    {
        Schema::table('numbering_register', function (Blueprint $table) {
            $table->dropColumn('account_name');
        });

        Schema::table('invoice_metadata', function (Blueprint $table) {
            $table->dropColumn('account_name');
        });
    }
};
