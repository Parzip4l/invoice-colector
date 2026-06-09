<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('registration_number');
            $table->foreignUlid('transaction_type_id')->constrained('transaction_types');
            $table->foreignUlid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignUlid('division_id')->constrained('divisions');
            $table->foreignUlid('department_id')->constrained('departments');
            $table->foreignUlid('memo_request_id')->nullable()->constrained('memo_requests')->nullOnDelete();
            $table->foreignUlid('agreement_reference_id')->nullable()->constrained('agreement_references')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('contract_number')->nullable();
            $table->decimal('contract_value', 18, 2)->nullable();
            $table->string('status');
            $table->string('current_step');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique('registration_number');
            $table->index('transaction_type_id');
            $table->index('vendor_id');
            $table->index('division_id');
            $table->index('department_id');
            $table->index('memo_request_id');
            $table->index('agreement_reference_id');
            $table->index('status');
            $table->index('current_step');
            $table->index('created_by');
            $table->index('submitted_at');
            $table->index('completed_at');
            $table->index(['status', 'current_step', 'created_at']);
            $table->index(['transaction_type_id', 'status']);
            $table->index(['vendor_id', 'created_at']);
            $table->index(['division_id', 'department_id', 'created_at']);
        });

        Schema::create('transaction_parties', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('party_type');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('status')->default('ACTIVE');
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('party_type');
            $table->index('user_id');
            $table->index('vendor_id');
            $table->index(['transaction_id', 'party_type']);
        });

        Schema::create('invoice_metadata', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('transaction_id')->unique()->constrained('transactions')->cascadeOnDelete();
            $table->foreignUlid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('invoice_number');
            $table->date('invoice_date')->nullable();
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('memo_number')->nullable();
            $table->string('contract_number')->nullable();
            $table->decimal('contract_value', 18, 2)->nullable();
            $table->decimal('invoice_value', 18, 2)->nullable();
            $table->decimal('ppn_value', 18, 2)->nullable();
            $table->decimal('pph_value', 18, 2)->nullable();
            $table->text('description')->nullable();
            $table->date('received_date')->nullable();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('vendor_id');
            $table->index('invoice_number');
            $table->index('invoice_date');
            $table->index('memo_number');
            $table->index('contract_number');
            $table->index('received_date');
            $table->unique(['vendor_id', 'invoice_number']);
        });

        Schema::create('transaction_status_histories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('from_step')->nullable();
            $table->string('to_step');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('transaction_id');
            $table->index('to_status');
            $table->index('to_step');
            $table->index(['transaction_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_status_histories');
        Schema::dropIfExists('invoice_metadata');
        Schema::dropIfExists('transaction_parties');
        Schema::dropIfExists('transactions');
    }
};
