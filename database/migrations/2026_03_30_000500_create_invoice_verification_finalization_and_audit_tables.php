<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_register', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('transaction_id')->unique()->constrained('transactions')->cascadeOnDelete();
            $table->string('register_number');
            $table->string('vendor_name');
            $table->date('received_date');
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
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique('register_number');
            $table->index('transaction_id');
            $table->index('received_date');
            $table->index('invoice_number');
            $table->index('memo_number');
            $table->index('contract_number');
            $table->index('generated_at');
            $table->index(['received_date', 'created_at']);
        });

        Schema::create('compiled_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('transaction_id')->unique()->constrained('transactions')->cascadeOnDelete();
            $table->string('compiled_file_name');
            $table->string('compiled_file_disk');
            $table->string('compiled_file_path');
            $table->unsignedInteger('total_files')->default(0);
            $table->timestamp('compiled_at');
            $table->foreignId('compiled_by')->constrained('users');
            $table->string('archive_disk')->nullable();
            $table->string('archive_path')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('compiled_at');
            $table->index('compiled_by');
            $table->index('archived_at');
        });

        Schema::create('compiled_document_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('compiled_document_id')->constrained('compiled_documents')->cascadeOnDelete();
            $table->string('source_type');
            $table->string('source_id');
            $table->string('included_as');
            $table->unsignedSmallInteger('sort_order');
            $table->timestamps();

            $table->index('compiled_document_id');
            $table->index('source_type');
            $table->index('source_id');
            $table->index(['compiled_document_id', 'sort_order']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('actor_type');
            $table->string('actor_id');
            $table->string('module');
            $table->string('action');
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->json('old_value_json')->nullable();
            $table->json('new_value_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('transaction_id');
            $table->index('actor_type');
            $table->index('actor_id');
            $table->index('module');
            $table->index('action');
            $table->index('reference_type');
            $table->index('reference_id');
            $table->index('created_at');
            $table->index(['transaction_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('compiled_document_items');
        Schema::dropIfExists('compiled_documents');
        Schema::dropIfExists('numbering_register');
    }
};
