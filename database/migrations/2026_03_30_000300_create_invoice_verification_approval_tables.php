<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('document_code');
            $table->foreignUlid('template_reference_id')->nullable()->constrained('template_references')->nullOnDelete();
            $table->string('document_number')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_disk')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('approval_mode');
            $table->string('generation_status');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('document_code');
            $table->index('approval_mode');
            $table->index('generation_status');
            $table->index(['transaction_id', 'document_code', 'version']);
        });

        Schema::create('approval_flows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('transaction_type_id')->constrained('transaction_types');
            $table->string('document_code')->nullable();
            $table->unsignedSmallInteger('step_no');
            $table->string('step_code');
            $table->string('step_name');
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->index('transaction_type_id');
            $table->index('document_code');
            $table->unique(['transaction_type_id', 'document_code', 'step_no']);
        });

        Schema::create('approval_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignUlid('generated_document_id')->nullable()->constrained('generated_documents')->cascadeOnDelete();
            $table->foreignUlid('approval_flow_id')->constrained('approval_flows');
            $table->foreignId('approver_user_id')->constrained('users');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamp('action_at')->nullable();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('generated_document_id');
            $table->index('approval_flow_id');
            $table->index('approver_user_id');
            $table->index('status');
            $table->index('action_at');
            $table->index(['transaction_id', 'status']);
            $table->index(['approver_user_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_transactions');
        Schema::dropIfExists('approval_flows');
        Schema::dropIfExists('generated_documents');
    }
};
