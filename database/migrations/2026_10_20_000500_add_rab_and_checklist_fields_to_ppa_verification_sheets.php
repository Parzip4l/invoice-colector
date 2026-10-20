<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppa_verification_sheets', function (Blueprint $table) {
            $table->json('budget_items_json')->nullable()->after('rejection_notes');
            $table->json('checklist_items_json')->nullable()->after('budget_items_json');
            $table->decimal('advance_payment_value', 18, 2)->nullable()->default(0)->after('checklist_items_json');
            $table->decimal('exchange_rate_value', 18, 4)->nullable()->default(1.00)->after('advance_payment_value');
            $table->string('accounting_signer_name')->nullable()->after('exchange_rate_value');
        });
    }

    public function down(): void
    {
        Schema::table('ppa_verification_sheets', function (Blueprint $table) {
            $table->dropColumn([
                'budget_items_json',
                'checklist_items_json',
                'advance_payment_value',
                'exchange_rate_value',
                'accounting_signer_name',
            ]);
        });
    }
};
