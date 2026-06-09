<?php

namespace Database\Seeders\InvoiceVerification;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class InvoiceVerificationSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->call([
            DirectorySeeder::class,
            UserSeeder::class,
            MasterDataSeeder::class,
            ApprovalFlowSeeder::class,
            DemoWorkflowSeeder::class,
        ]);

        Schema::enableForeignKeyConstraints();
    }
}
