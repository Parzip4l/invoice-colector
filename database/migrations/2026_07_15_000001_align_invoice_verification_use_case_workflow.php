<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_number_sequences', function (Blueprint $table) {
            $table->string('prefix')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::table('divisions', function (Blueprint $table) {
            $table->decimal('petty_cash_ceiling', 18, 2)->nullable()->after('name');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->foreignUlid('parent_spu_transaction_id')->nullable()->after('agreement_reference_id')->constrained('transactions')->nullOnDelete();
            $table->string('activity_name')->nullable()->after('title');
            $table->string('transaction_bank_name')->nullable()->after('activity_name');
            $table->string('transaction_account_number')->nullable()->after('transaction_bank_name');
            $table->decimal('spu_amount', 18, 2)->nullable()->after('contract_value');
            $table->decimal('accountability_amount', 18, 2)->nullable()->after('spu_amount');
            $table->decimal('remaining_amount', 18, 2)->nullable()->after('accountability_amount');
            $table->decimal('petty_cash_ceiling_snapshot', 18, 2)->nullable()->after('remaining_amount');
            $table->decimal('petty_cash_remaining_amount', 18, 2)->nullable()->after('petty_cash_ceiling_snapshot');
            $table->decimal('petty_cash_top_up_amount', 18, 2)->nullable()->after('petty_cash_remaining_amount');
            $table->string('period')->nullable()->after('petty_cash_top_up_amount');
            $table->timestamp('scheduled_payment_at')->nullable()->after('completed_at');
            $table->string('payment_proof_file_name')->nullable()->after('scheduled_payment_at');
            $table->string('payment_proof_file_disk')->nullable()->after('payment_proof_file_name');
            $table->string('payment_proof_file_path')->nullable()->after('payment_proof_file_disk');
            $table->string('payment_proof_mime_type')->nullable()->after('payment_proof_file_path');
            $table->unsignedBigInteger('payment_proof_file_size')->nullable()->after('payment_proof_mime_type');
            $table->timestamp('payment_proof_uploaded_at')->nullable()->after('payment_proof_file_size');
            $table->foreignId('payment_proof_uploaded_by')->nullable()->after('payment_proof_uploaded_at')->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable()->after('payment_proof_uploaded_by');

            $table->index('owner_user_id');
            $table->index('parent_spu_transaction_id');
            $table->index(['status', 'scheduled_payment_at']);
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['owner_user_id']);
            $table->dropIndex(['parent_spu_transaction_id']);
            $table->dropIndex(['status', 'scheduled_payment_at']);
            $table->dropIndex(['paid_at']);
            $table->dropForeign(['owner_user_id']);
            $table->dropForeign(['parent_spu_transaction_id']);
            $table->dropForeign(['payment_proof_uploaded_by']);
            $table->dropColumn([
                'owner_user_id',
                'parent_spu_transaction_id',
                'activity_name',
                'transaction_bank_name',
                'transaction_account_number',
                'spu_amount',
                'accountability_amount',
                'remaining_amount',
                'petty_cash_ceiling_snapshot',
                'petty_cash_remaining_amount',
                'petty_cash_top_up_amount',
                'period',
                'scheduled_payment_at',
                'payment_proof_file_name',
                'payment_proof_file_disk',
                'payment_proof_file_path',
                'payment_proof_mime_type',
                'payment_proof_file_size',
                'payment_proof_uploaded_at',
                'payment_proof_uploaded_by',
                'paid_at',
            ]);
        });

        Schema::table('divisions', function (Blueprint $table) {
            $table->dropColumn('petty_cash_ceiling');
        });

        Schema::dropIfExists('transaction_number_sequences');
    }
};
