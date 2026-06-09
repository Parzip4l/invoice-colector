<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('ldap_code')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique('ldap_code');
            $table->index('is_active');
            $table->index('name');
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('ldap_code')->nullable();
            $table->foreignUlid('division_id')->constrained('divisions');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique('ldap_code');
            $table->index('division_id');
            $table->index('is_active');
            $table->index('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('ldap_uid')->nullable()->after('id');
            $table->string('employee_number')->nullable()->after('ldap_uid');
            $table->foreignUlid('department_id')->nullable()->after('email')->constrained('departments')->nullOnDelete();
            $table->foreignUlid('division_id')->nullable()->after('department_id')->constrained('divisions')->nullOnDelete();
            $table->string('role_code')->nullable()->after('division_id');
            $table->boolean('is_active')->default(true)->after('role_code');
            $table->timestamp('last_synced_at')->nullable()->after('is_active');

            $table->unique('ldap_uid');
            $table->index('department_id');
            $table->index('division_id');
            $table->index('role_code');
            $table->index('is_active');
        });

        Schema::create('banks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code');
            $table->string('name');
            $table->timestamps();

            $table->unique('code');
            $table->index('name');
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('vendor_code')->nullable();
            $table->string('name');
            $table->string('npwp')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->foreignUlid('default_bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->string('default_account_number')->nullable();
            $table->timestamps();

            $table->unique('vendor_code');
            $table->index('name');
            $table->index('npwp');
            $table->index('default_bank_id');
        });

        Schema::create('memo_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('memo_number');
            $table->date('memo_date');
            $table->string('subject');
            $table->text('description')->nullable();
            $table->foreignUlid('division_id')->constrained('divisions');
            $table->foreignUlid('department_id')->constrained('departments');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique('memo_number');
            $table->index('memo_date');
            $table->index('division_id');
            $table->index('department_id');
            $table->index('created_by');
        });

        Schema::create('transaction_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code');
            $table->string('name');
            $table->string('upload_scheme');
            $table->timestamps();

            $table->unique('code');
            $table->index('upload_scheme');
        });

        Schema::create('agreement_references', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('contract_number');
            $table->string('title');
            $table->decimal('contract_value', 18, 2)->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expired_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('contract_number');
            $table->index('vendor_id');
            $table->index('effective_date');
        });

        Schema::create('template_references', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code');
            $table->string('name');
            $table->string('template_type');
            $table->foreignUlid('transaction_type_id')->nullable()->constrained('transaction_types')->nullOnDelete();
            $table->string('document_code')->nullable();
            $table->string('file_path')->nullable();
            $table->json('configuration_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('code');
            $table->index('template_type');
            $table->index('transaction_type_id');
            $table->index('document_code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_references');
        Schema::dropIfExists('agreement_references');
        Schema::dropIfExists('transaction_types');
        Schema::dropIfExists('memo_requests');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('banks');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('division_id');
            $table->dropUnique(['ldap_uid']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['division_id']);
            $table->dropIndex(['role_code']);
            $table->dropIndex(['is_active']);
            $table->dropColumn([
                'ldap_uid',
                'employee_number',
                'role_code',
                'is_active',
                'last_synced_at',
            ]);
        });

        Schema::dropIfExists('departments');
        Schema::dropIfExists('divisions');
    }
};
