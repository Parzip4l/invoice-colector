<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreement_references', function (Blueprint $table) {
            $table->foreignUlid('division_id')->nullable()->after('vendor_id')->constrained('divisions')->nullOnDelete();
            $table->foreignUlid('department_id')->nullable()->after('division_id')->constrained('departments')->nullOnDelete();
            $table->string('file_name')->nullable()->after('expired_at');
            $table->string('file_path')->nullable()->after('file_name');
            $table->string('file_disk')->nullable()->after('file_path');
            $table->string('file_extension', 20)->nullable()->after('file_disk');
            $table->string('mime_type')->nullable()->after('file_extension');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->timestamp('uploaded_at')->nullable()->after('file_size');

            $table->index('division_id');
            $table->index('department_id');
            $table->index('uploaded_at');
            $table->index(['division_id', 'department_id', 'contract_number']);
        });
    }

    public function down(): void
    {
        Schema::table('agreement_references', function (Blueprint $table) {
            $table->dropIndex(['division_id']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['uploaded_at']);
            $table->dropIndex(['division_id', 'department_id', 'contract_number']);
            $table->dropForeign(['division_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn([
                'division_id',
                'department_id',
                'file_name',
                'file_path',
                'file_disk',
                'file_extension',
                'mime_type',
                'file_size',
                'uploaded_at',
            ]);
        });
    }
};
