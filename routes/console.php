<?php

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\Division;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('invoice:whitelist-user
    {email : User email}
    {--name= : Display name}
    {--role=ADMIN_DIVISI : Role code}
    {--password= : Optional local password}
    {--ldap-uid= : Optional LDAP UID}
    {--employee-number= : Optional employee number}
    {--division-code= : Optional division LDAP code to link/create}
    {--division-name= : Optional division name when creating division}
    {--department-code= : Optional department LDAP code to link/create}
    {--department-name= : Optional department name when creating department}
    {--inactive : Create/update user as inactive}
}', function () {
    $email = Str::lower(trim((string) $this->argument('email')));
    $roleCode = strtoupper(trim((string) $this->option('role')));
    $role = RoleCode::tryFrom($roleCode);

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Email tidak valid.');

        return Command::FAILURE;
    }

    if (! $role) {
        $this->error('Role tidak valid. Pilihan: '.collect(RoleCode::cases())->pluck('value')->implode(', '));

        return Command::FAILURE;
    }

    if ($role === RoleCode::VENDOR) {
        $this->error('Command ini khusus whitelist user internal. Vendor dibuat lewat master data vendor.');

        return Command::FAILURE;
    }

    $division = null;
    $divisionCode = trim((string) $this->option('division-code'));

    if ($divisionCode !== '') {
        $division = Division::query()->updateOrCreate(
            ['ldap_code' => $divisionCode],
            [
                'name' => trim((string) $this->option('division-name')) ?: $divisionCode,
                'is_active' => true,
                'last_synced_at' => now(),
            ],
        );
    }

    $department = null;
    $departmentCode = trim((string) $this->option('department-code'));

    if ($departmentCode !== '') {
        if (! $division) {
            $this->error('department-code membutuhkan division-code.');

            return Command::FAILURE;
        }

        $department = Department::query()->updateOrCreate(
            ['ldap_code' => $departmentCode],
            [
                'division_id' => $division->id,
                'name' => trim((string) $this->option('department-name')) ?: $departmentCode,
                'is_active' => true,
                'last_synced_at' => now(),
            ],
        );
    }

    $password = (string) $this->option('password');
    $payload = [
        'name' => trim((string) $this->option('name')) ?: Str::of($email)->before('@')->replace(['.', '_', '-'], ' ')->title()->toString(),
        'ldap_uid' => trim((string) $this->option('ldap-uid')) ?: null,
        'employee_number' => trim((string) $this->option('employee-number')) ?: null,
        'role_code' => $role->value,
        'division_id' => $division?->id,
        'department_id' => $department?->id,
        'is_active' => ! $this->option('inactive'),
        'last_synced_at' => now(),
        'email_verified_at' => now(),
    ];

    if ($password !== '') {
        $payload['password'] = Hash::make($password);
    }

    $user = User::query()->updateOrCreate(
        ['email' => $email],
        $payload,
    );

    $this->info(sprintf(
        'Whitelist user %s (%s) sudah %s.',
        $user->email,
        $role->value,
        $user->wasRecentlyCreated ? 'dibuat' : 'diupdate',
    ));

    if ($password === '') {
        $this->warn('Password lokal tidak diset. Login internal akan memakai LDAP, atau set LDAP_LOCAL_FALLBACK=true dan jalankan ulang dengan --password.');
    }

    return Command::SUCCESS;
})->purpose('Create or update an active internal invoice verification whitelist user.');
