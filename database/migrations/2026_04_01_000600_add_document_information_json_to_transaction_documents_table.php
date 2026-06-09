<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_documents', function (Blueprint $table) {
            $table->json('document_information_json')->nullable()->after('document_label');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_documents', function (Blueprint $table) {
            $table->dropColumn('document_information_json');
        });
    }
};
