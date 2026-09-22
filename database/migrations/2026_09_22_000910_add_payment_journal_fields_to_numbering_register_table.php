<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('numbering_register', function (Blueprint $table) {
            $table->boolean('hardcopy_received')->default(false)->after('upload_date');
            $table->decimal('discount_deduction_stamp_value', 18, 2)->nullable()->after('pph_value');
            $table->string('gr_number')->nullable()->after('tax_invoice_date');
            $table->string('journal_type')->nullable()->after('gr_number');
            $table->date('payment_expedition_date')->nullable()->after('journal_type');
            $table->date('payment_date')->nullable()->after('payment_expedition_date');
            $table->string('journal_status')->nullable()->after('payment_date');
            $table->date('return_date')->nullable()->after('journal_status');
            $table->decimal('return_amount', 18, 2)->nullable()->after('return_date');
            $table->string('spuk_status')->nullable()->after('return_amount');
            $table->text('spuk_notes')->nullable()->after('spuk_status');

            $table->index('hardcopy_received');
            $table->index('payment_expedition_date');
            $table->index('payment_date');
            $table->index('journal_type');
            $table->index('journal_status');
            $table->index('spuk_status');
        });
    }

    public function down(): void
    {
        Schema::table('numbering_register', function (Blueprint $table) {
            $table->dropIndex(['hardcopy_received']);
            $table->dropIndex(['payment_expedition_date']);
            $table->dropIndex(['payment_date']);
            $table->dropIndex(['journal_type']);
            $table->dropIndex(['journal_status']);
            $table->dropIndex(['spuk_status']);

            $table->dropColumn([
                'hardcopy_received',
                'discount_deduction_stamp_value',
                'gr_number',
                'journal_type',
                'payment_expedition_date',
                'payment_date',
                'journal_status',
                'return_date',
                'return_amount',
                'spuk_status',
                'spuk_notes',
            ]);
        });
    }
};
