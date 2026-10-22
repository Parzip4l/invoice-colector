<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compiled_documents', function (Blueprint $table) {
            $table->string('compiled_file_disk')->nullable()->change();
            $table->string('compiled_file_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('compiled_documents')
            ->whereNull('compiled_file_disk')
            ->update(['compiled_file_disk' => 'local']);
        DB::table('compiled_documents')
            ->whereNull('compiled_file_path')
            ->update(['compiled_file_path' => '']);

        Schema::table('compiled_documents', function (Blueprint $table) {
            $table->string('compiled_file_disk')->nullable(false)->change();
            $table->string('compiled_file_path')->nullable(false)->change();
        });
    }
};
