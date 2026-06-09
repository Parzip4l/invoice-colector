<?php

namespace Database\Seeders\InvoiceVerification;

use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\Division;
use Illuminate\Database\Seeder;

class DirectorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['ldap_code' => 'DIV-OPS', 'name' => 'Divisi Operasional'],
            ['ldap_code' => 'DIV-KEU', 'name' => 'Divisi Keuangan'],
        ] as $division) {
            Division::updateOrCreate(
                ['ldap_code' => $division['ldap_code']],
                [
                    'name' => $division['name'],
                    'is_active' => true,
                    'last_synced_at' => now(),
                ],
            );
        }

        $divisions = Division::query()->get()->keyBy('ldap_code');

        foreach ([
            ['ldap_code' => 'DEP-OPS-ADM', 'division' => 'DIV-OPS', 'name' => 'Departemen Administrasi Operasional'],
            ['ldap_code' => 'DEP-OPS-PROJ', 'division' => 'DIV-OPS', 'name' => 'Departemen Proyek Operasional'],
            ['ldap_code' => 'DEP-KEU-AK', 'division' => 'DIV-KEU', 'name' => 'Departemen Akuntansi'],
        ] as $department) {
            Department::updateOrCreate(
                ['ldap_code' => $department['ldap_code']],
                [
                    'division_id' => $divisions[$department['division']]->id,
                    'name' => $department['name'],
                    'is_active' => true,
                    'last_synced_at' => now(),
                ],
            );
        }

    }
}
