<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memo_requests', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('description');
            $table->string('file_path')->nullable()->after('file_name');
            $table->string('file_disk')->nullable()->after('file_path');
            $table->string('file_extension', 20)->nullable()->after('file_disk');
            $table->string('mime_type')->nullable()->after('file_extension');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->timestamp('uploaded_at')->nullable()->after('file_size');

            $table->index('uploaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('memo_requests', function (Blueprint $table) {
            $table->dropIndex(['uploaded_at']);
            $table->dropColumn([
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
