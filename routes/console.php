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

    $existingUser = User::query()->where('email', $email)->first();

    if ($password !== '') {
        $payload['password'] = Hash::make($password);
    } elseif (! $existingUser) {
        $payload['password'] = Hash::make(Str::random(40));
    }

    $user = $existingUser ?: new User(['email' => $email]);
    $user->fill($payload);
    $user->save();

    $this->info(sprintf(
        'Whitelist user %s (%s) sudah %s.',
        $user->email,
        $role->value,
        $existingUser ? 'diupdate' : 'dibuat',
    ));

    if ($password === '') {
        $this->warn('Password lokal tidak diset. Login internal akan memakai LDAP, atau set LDAP_LOCAL_FALLBACK=true dan jalankan ulang dengan --password.');
    }

    return Command::SUCCESS;
})->purpose('Create or update an active internal invoice verification whitelist user.');

Artisan::command('ldap:test-login
    {email : Email/login to search and test}
    {--password= : Optional user password. If omitted, you will be prompted.}
}', function () {
    $email = Str::lower(trim((string) $this->argument('email')));
    $password = (string) $this->option('password');
    $host = trim((string) config('ldap.host', 'localhost'));
    $connectionHost = preg_match('/^ldaps?:\/\//i', $host)
        ? $host
        : (filter_var(config('ldap.use_ssl'), FILTER_VALIDATE_BOOLEAN) ? 'ldaps://' : 'ldap://').$host;

    $this->line('LDAP_ENABLED: '.var_export(config('ldap.enabled'), true));
    $this->line('LDAP_DOMAIN: '.(config('ldap.domain') ?: '(empty)'));
    $this->line('LDAP_HOST: '.config('ldap.host'));
    $this->line('LDAP_CONNECTION_HOST: '.$connectionHost);
    $this->line('LDAP_PORT: '.config('ldap.port'));
    $this->line('LDAP_BASE_DN: '.(config('ldap.base_dn') ?: '(empty)'));
    $this->line('LDAP_GROUP_DN: '.(config('ldap.group_dn') ?: '(empty)'));
    $this->line('LDAP_BIND_MODE: '.config('ldap.bind_mode'));
    $this->line('LDAP_LOGIN_ATTRIBUTE: '.config('ldap.login_attribute'));
    $this->line('LDAP_SSL: '.var_export(config('ldap.use_ssl'), true));
    $this->line('LDAP_TLS: '.var_export(config('ldap.use_tls'), true));

    if (! extension_loaded('ldap')) {
        $this->error('PHP LDAP extension belum terinstall di container.');

        return Command::FAILURE;
    }

    $connection = @ldap_connect($connectionHost, (int) config('ldap.port'));

    if (! $connection) {
        $this->error('Tidak bisa membuat LDAP connection. Cek LDAP_HOST dan LDAP_PORT.');

        return Command::FAILURE;
    }

    ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, (int) config('ldap.timeout', 5));
    ldap_set_option($connection, LDAP_OPT_REFERRALS, filter_var(config('ldap.follow_referrals'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0);
    $this->info('Connection object OK.');

    if (filter_var(config('ldap.use_tls'), FILTER_VALIDATE_BOOLEAN)) {
        if (@ldap_start_tls($connection) !== true) {
            $this->error('StartTLS gagal: '.ldap_error($connection));

            return Command::FAILURE;
        }

        $this->info('StartTLS OK.');
    }

    $serviceUsername = trim((string) config('ldap.username', ''));
    $servicePassword = (string) config('ldap.password', '');
    $baseDn = trim((string) config('ldap.base_dn', ''));
    $userDn = $email;
    $bindMode = strtolower(trim((string) config('ldap.bind_mode', 'user'))) === 'service' ? 'service' : 'user';

    if ($bindMode === 'user') {
        if ($password === '') {
            $password = (string) $this->secret('Masukkan password user LDAP');
        }

        if (@ldap_bind($connection, $email, $password) !== true) {
            $this->error('User bind gagal: '.ldap_error($connection));

            return Command::FAILURE;
        }

        $this->info('User bind OK.');

        if ($baseDn !== '') {
            $attribute = preg_replace('/[^a-zA-Z0-9_.-]/', '', (string) config('ldap.login_attribute', 'userPrincipalName')) ?: 'userPrincipalName';
            $filter = sprintf('(%s=%s)', $attribute, ldap_escape($email, '', LDAP_ESCAPE_FILTER));
            $this->line('Search filter: '.$filter);

            $search = @ldap_search($connection, $baseDn, $filter, ['dn', 'displayName', 'mail', 'sAMAccountName'], 0, 1);

            if (! $search) {
                $this->error('LDAP search gagal setelah user bind: '.ldap_error($connection));

                return Command::FAILURE;
            }

            $entries = ldap_get_entries($connection, $search);

            if (($entries['count'] ?? 0) < 1 || empty($entries[0]['dn'])) {
                $this->warn('User bind berhasil, tapi user tidak ditemukan saat search.');
            } else {
                $this->info('User DN ditemukan: '.((string) $entries[0]['dn']));
            }
        }

        $this->info('LDAP login seharusnya berhasil.');

        return Command::SUCCESS;
    }

    if ($serviceUsername !== '' && $baseDn !== '') {
        if (@ldap_bind($connection, $serviceUsername, $servicePassword) !== true) {
            $this->error('Service bind gagal: '.ldap_error($connection));

            return Command::FAILURE;
        }

        $this->info('Service bind OK.');

        $attribute = preg_replace('/[^a-zA-Z0-9_.-]/', '', (string) config('ldap.login_attribute', 'mail')) ?: 'mail';
        $filter = sprintf('(%s=%s)', $attribute, ldap_escape($email, '', LDAP_ESCAPE_FILTER));
        $this->line('Search filter: '.$filter);

        $search = @ldap_search($connection, $baseDn, $filter, ['dn'], 0, 1);

        if (! $search) {
            $this->error('LDAP search gagal: '.ldap_error($connection));

            return Command::FAILURE;
        }

        $entries = ldap_get_entries($connection, $search);

        if (($entries['count'] ?? 0) < 1 || empty($entries[0]['dn'])) {
            $this->error('User tidak ditemukan di LDAP dengan filter tersebut.');

            return Command::FAILURE;
        }

        $userDn = (string) $entries[0]['dn'];
        $this->info('User DN ditemukan: '.$userDn);
    } else {
        $this->warn('LDAP_USERNAME atau LDAP_BASE_DN kosong. Test bind user langsung memakai email sebagai DN.');
    }

    if ($password === '') {
        $password = (string) $this->secret('Masukkan password user LDAP');
    }

    if (@ldap_bind($connection, $userDn, $password) !== true) {
        $this->error('User bind gagal: '.ldap_error($connection));

        return Command::FAILURE;
    }

    $this->info('User bind OK. LDAP login seharusnya berhasil.');

    return Command::SUCCESS;
})->purpose('Diagnose LDAP connection, search, and user bind for login.');
