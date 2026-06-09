<?php

namespace Database\Seeders\InvoiceVerification;

use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\Division;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $users = [
            [
                'name' => 'Admin Divisi Demo',
                'email' => 'admin.divisi@demo.local',
                'ldap_uid' => 'uid-admin-divisi',
                'employee_number' => 'EMP-0001',
                'role_code' => RoleCode::ADMIN_DIVISI,
                'division_code' => 'DIV-OPS',
                'department_code' => 'DEP-OPS-ADM',
            ],
            [
                'name' => 'Kepala Departemen Demo',
                'email' => 'kepala.departemen@demo.local',
                'ldap_uid' => 'uid-kadept',
                'employee_number' => 'EMP-0002',
                'role_code' => RoleCode::KEPALA_DEPARTEMEN,
                'division_code' => 'DIV-OPS',
                'department_code' => 'DEP-OPS-ADM',
            ],
            [
                'name' => 'Kepala Divisi Demo',
                'email' => 'kepala.divisi@demo.local',
                'ldap_uid' => 'uid-kadiv',
                'employee_number' => 'EMP-0003',
                'role_code' => RoleCode::KEPALA_DIVISI,
                'division_code' => 'DIV-OPS',
                'department_code' => 'DEP-OPS-PROJ',
            ],
            [
                'name' => 'User Divisi Demo',
                'email' => 'user.divisi@demo.local',
                'ldap_uid' => 'uid-user-divisi',
                'employee_number' => 'EMP-0004',
                'role_code' => RoleCode::USER_DIVISI,
                'division_code' => 'DIV-OPS',
                'department_code' => 'DEP-OPS-PROJ',
            ],
            [
                'name' => 'Vendor Demo',
                'email' => 'vendor@demo.local',
                'ldap_uid' => null,
                'employee_number' => null,
                'role_code' => RoleCode::VENDOR,
                'division_code' => 'DIV-OPS',
                'department_code' => 'DEP-OPS-PROJ',
            ],
            [
                'name' => 'Vendor Logistik Demo',
                'email' => 'vendor.logistik@demo.local',
                'ldap_uid' => null,
                'employee_number' => null,
                'role_code' => RoleCode::VENDOR,
                'division_code' => 'DIV-OPS',
                'department_code' => 'DEP-OPS-ADM',
            ],
            [
                'name' => 'Vendor Teknologi Demo',
                'email' => 'vendor.teknologi@demo.local',
                'ldap_uid' => null,
                'employee_number' => null,
                'role_code' => RoleCode::VENDOR,
                'division_code' => 'DIV-OPS',
                'department_code' => 'DEP-OPS-ADM',
            ],
            [
                'name' => 'Vendor Konstruksi Demo',
                'email' => 'vendor.konstruksi@demo.local',
                'ldap_uid' => null,
                'employee_number' => null,
                'role_code' => RoleCode::VENDOR,
                'division_code' => 'DIV-OPS',
                'department_code' => 'DEP-OPS-ADM',
            ],
            [
                'name' => 'Akuntansi Demo',
                'email' => 'akuntansi@demo.local',
                'ldap_uid' => 'uid-akuntansi',
                'employee_number' => 'EMP-0005',
                'role_code' => RoleCode::AKUNTANSI,
                'division_code' => 'DIV-KEU',
                'department_code' => 'DEP-KEU-AK',
            ],
            [
                'name' => 'Finance Demo',
                'email' => 'finance@demo.local',
                'ldap_uid' => 'uid-finance',
                'employee_number' => 'EMP-0006',
                'role_code' => RoleCode::FINANCE,
                'division_code' => 'DIV-KEU',
                'department_code' => 'DEP-KEU-AK',
            ],
        ];

        foreach ($users as $payload) {
            $divisionId = Division::query()->where('ldap_code', $payload['division_code'])->value('id');
            $departmentId = Department::query()->where('ldap_code', $payload['department_code'])->value('id');

            $attributes = [
                'name' => $payload['name'],
                'ldap_uid' => $payload['ldap_uid'],
                'employee_number' => $payload['employee_number'],
                'role_code' => $payload['role_code']->value,
                'division_id' => (string) $divisionId,
                'department_id' => (string) $departmentId,
                'is_active' => true,
                'last_synced_at' => now(),
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
                'updated_at' => now(),
            ];

            $existing = DB::table('users')->where('email', $payload['email'])->exists();

            if ($existing) {
                DB::table('users')
                    ->where('email', $payload['email'])
                    ->update($attributes);

                continue;
            }

            DB::table('users')->insert([
                'email' => $payload['email'],
                ...$attributes,
                'created_at' => now(),
            ]);
        }

        Schema::enableForeignKeyConstraints();
    }
}
