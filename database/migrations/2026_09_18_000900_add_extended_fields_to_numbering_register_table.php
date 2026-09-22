<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('numbering_register', function (Blueprint $table) {
            $table->date('upload_date')->nullable()->after('invoice_date');
            $table->string('tax_invoice_number')->nullable()->after('pph_value');
            $table->date('tax_invoice_date')->nullable()->after('tax_invoice_number');
            $table->string('department_head_name')->nullable()->after('description');
            $table->string('division_name')->nullable()->after('department_head_name');

            $table->index('upload_date');
            $table->index('tax_invoice_number');
            $table->index('division_name');
        });
    }

    public function down(): void
    {
        Schema::table('numbering_register', function (Blueprint $table) {
            $table->dropIndex(['upload_date']);
            $table->dropIndex(['tax_invoice_number']);
            $table->dropIndex(['division_name']);

            $table->dropColumn([
                'upload_date',
                'tax_invoice_number',
                'tax_invoice_date',
                'department_head_name',
                'division_name',
            ]);
        });
    }
};
