<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code');
            $table->string('name');
            $table->foreignUlid('transaction_type_id')->nullable()->constrained('transaction_types')->nullOnDelete();
            $table->string('source_type');
            $table->string('upload_mode');
            $table->string('approval_mode');
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['code', 'transaction_type_id']);
            $table->index('transaction_type_id');
            $table->index('source_type');
            $table->index('upload_mode');
            $table->index('approval_mode');
            $table->index('is_required');
            $table->index(['transaction_type_id', 'sort_order']);
        });

        Schema::create('transaction_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignUlid('document_type_id')->constrained('document_types');
            $table->string('source_actor');
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('uploaded_by_vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('document_label')->nullable();
            $table->string('file_name');
            $table->string('file_disk');
            $table->string('file_path');
            $table->string('file_extension');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('version')->default(1);
            $table->string('status');
            $table->boolean('is_latest')->default(true);
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('document_type_id');
            $table->index('source_actor');
            $table->index('uploaded_by_user_id');
            $table->index('uploaded_by_vendor_id');
            $table->index('status');
            $table->index('is_latest');
            $table->index('uploaded_at');
            $table->index(['transaction_id', 'document_type_id', 'is_latest']);
            $table->index(['transaction_id', 'status']);
            $table->index(['source_actor', 'status']);
            $table->index(['transaction_id', 'document_type_id', 'version']);
        });

        Schema::create('vendor_document_reviews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('transaction_document_id')->unique()->constrained('transaction_documents')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->constrained('users');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index('transaction_document_id');
            $table->index('reviewed_by');
            $table->index('status');
            $table->index('reviewed_at');
            $table->index(['status', 'reviewed_at']);
        });

        Schema::create('ppa_verification_sheets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('transaction_id')->unique()->constrained('transactions')->cascadeOnDelete();
            $table->string('status');
            $table->foreignId('filled_by_user_id')->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_notes')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_disk')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('status');
            $table->index('filled_by_user_id');
            $table->index('approved_by_user_id');
            $table->index(['transaction_id', 'status']);
        });

        Schema::create('ppa_verification_sheet_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('verification_sheet_id')->constrained('ppa_verification_sheets')->cascadeOnDelete();
            $table->foreignUlid('document_type_id')->constrained('document_types');
            $table->string('attachment_status');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('verification_sheet_id');
            $table->index('document_type_id');
            $table->index('attachment_status');
            $table->unique(['verification_sheet_id', 'document_type_id']);
        });

        Schema::create('accounting_verifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('transaction_id')->unique()->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('verifier_user_id')->constrained('users');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('verifier_user_id');
            $table->index('status');
            $table->index('verified_at');
            $table->index(['status', 'verified_at']);
        });

        Schema::create('accounting_verification_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('accounting_verification_id')->constrained('accounting_verifications')->cascadeOnDelete();
            $table->foreignUlid('transaction_document_id')->constrained('transaction_documents')->cascadeOnDelete();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('accounting_verification_id');
            $table->index('transaction_document_id');
            $table->index('status');
            $table->index(['accounting_verification_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_verification_items');
        Schema::dropIfExists('accounting_verifications');
        Schema::dropIfExists('ppa_verification_sheet_items');
        Schema::dropIfExists('ppa_verification_sheets');
        Schema::dropIfExists('vendor_document_reviews');
        Schema::dropIfExists('transaction_documents');
        Schema::dropIfExists('document_types');
    }
};
