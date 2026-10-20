<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_metadata', function (Blueprint $table) {
            $table->decimal('ppn_percentage', 8, 4)->nullable()->after('invoice_value');
            $table->decimal('pph_percentage', 8, 4)->nullable()->after('ppn_value');
            $table->decimal('pph_base_value', 18, 2)->nullable()->after('pph_percentage');
        });

        Schema::table('numbering_register', function (Blueprint $table) {
            $table->decimal('ppn_percentage', 8, 4)->nullable()->after('invoice_value');
            $table->decimal('pph_percentage', 8, 4)->nullable()->after('ppn_value');
            $table->decimal('pph_base_value', 18, 2)->nullable()->after('pph_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('numbering_register', function (Blueprint $table) {
            $table->dropColumn([
                'ppn_percentage',
                'pph_percentage',
                'pph_base_value',
            ]);
        });

        Schema::table('invoice_metadata', function (Blueprint $table) {
            $table->dropColumn([
                'ppn_percentage',
                'pph_percentage',
                'pph_base_value',
            ]);
        });
    }
};
