<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_transactions', function (Blueprint $table) {
            $table->unique(
                ['transaction_id', 'approval_flow_id'],
                'approval_transactions_transaction_flow_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('approval_transactions', function (Blueprint $table) {
            $table->dropUnique('approval_transactions_transaction_flow_unique');
        });
    }
};
