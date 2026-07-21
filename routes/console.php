<?php

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\Bank;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\Division;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

Artisan::command('eproc:import-csv
    {--vendors= : Path CSV vendor aktif}
    {--purchasing= : Path CSV list purchasing}
    {--division-code=EPROC : Default division code for imported purchasing departments}
    {--division-name=E-Procurement : Default division name for imported purchasing departments}
    {--created-by= : Optional created_by user email for agreement references}
}', function () {
    $vendorPath = (string) $this->option('vendors');
    $purchasingPath = (string) $this->option('purchasing');

    if ($vendorPath === '' && $purchasingPath === '') {
        $this->error('Isi minimal --vendors atau --purchasing.');

        return Command::FAILURE;
    }

    $createdBy = null;
    $createdByEmail = Str::lower(trim((string) $this->option('created-by')));
    $divisionCode = (string) $this->option('division-code');
    $divisionName = (string) $this->option('division-name');

    if ($createdByEmail !== '') {
        $createdBy = User::query()->whereRaw('LOWER(email) = ?', [$createdByEmail])->first();

        if (! $createdBy) {
            $this->error('User created-by tidak ditemukan: '.$createdByEmail);

            return Command::FAILURE;
        }
    }

    $stats = DB::transaction(function () use ($vendorPath, $purchasingPath, $createdBy, $divisionCode, $divisionName) {
        $stats = [
            'vendors_created' => 0,
            'vendors_updated' => 0,
            'banks_created' => 0,
            'agreements_created' => 0,
            'agreements_updated' => 0,
            'departments_created' => 0,
        ];

        if ($vendorPath !== '') {
            foreach (readCsvRows($vendorPath) as $row) {
                $vendorName = cleanImportValue($row['Nama Vendor'] ?? null);

                if ($vendorName === null) {
                    continue;
                }

                $bank = null;
                $bankName = cleanImportValue($row['Nama Bank'] ?? $row['Bank'] ?? null);

                if ($bankName !== null) {
                    $bank = Bank::query()->firstOrCreate(
                        ['name' => $bankName],
                        ['code' => makeImportCode($bankName)],
                    );

                    if ($bank->wasRecentlyCreated) {
                        $stats['banks_created']++;
                    }
                }

                $vendor = Vendor::query()
                    ->where('vendor_code', cleanImportValue($row['Nomor Eproc'] ?? null))
                    ->when(cleanImportValue($row['Nomor Eproc'] ?? null) === null, fn ($query) => $query->whereRaw('LOWER(name) = ?', [Str::lower($vendorName)]))
                    ->first();

                $payload = [
                    'vendor_code' => cleanImportValue($row['Nomor Eproc'] ?? null),
                    'name' => $vendorName,
                    'npwp' => cleanImportValue($row['NPWP'] ?? null),
                    'address' => cleanImportValue($row['Alamat Vendor'] ?? $row['Alamat Operasional'] ?? null),
                    'contact_name' => cleanImportValue($row['Nama PIC'] ?? null),
                    'contact_email' => Str::lower((string) cleanImportValue($row['Email PIC'] ?? $row['Email'] ?? null)) ?: null,
                    'contact_phone' => cleanImportValue($row['Nomor PIC'] ?? $row['Nomor Telpon'] ?? null),
                    'default_bank_id' => $bank?->id,
                    'default_account_number' => cleanImportValue($row['Nomor Rekening'] ?? $row['Account Number'] ?? null),
                ];

                if ($vendor) {
                    $vendor->forceFill($payload)->save();
                    $stats['vendors_updated']++;
                } else {
                    Vendor::query()->create($payload);
                    $stats['vendors_created']++;
                }
            }
        }

        if ($purchasingPath !== '') {
            $division = Division::query()->firstOrCreate(
                ['ldap_code' => $divisionCode],
                [
                    'name' => $divisionName,
                    'is_active' => true,
                    'last_synced_at' => now(),
                ],
            );

            foreach (readCsvRows($purchasingPath) as $row) {
                $contractNumber = cleanImportValue($row['Nomor PO'] ?? null);

                if ($contractNumber === null) {
                    continue;
                }

                $vendorName = cleanImportValue($row['Nama Vendor'] ?? null);
                $vendor = $vendorName
                    ? Vendor::query()->whereRaw('LOWER(name) = ?', [Str::lower($vendorName)])->first()
                    : null;

                if (! $vendor && $vendorName) {
                    $vendor = Vendor::query()->create([
                        'name' => $vendorName,
                    ]);
                    $stats['vendors_created']++;
                }

                $departmentName = cleanImportValue($row['Departemen'] ?? null) ?: 'E-Procurement';
                $department = Department::query()
                    ->where('division_id', $division->id)
                    ->whereRaw('LOWER(name) = ?', [Str::lower($departmentName)])
                    ->first();

                if (! $department) {
                    $department = Department::query()->create([
                        'division_id' => $division->id,
                        'ldap_code' => null,
                        'name' => $departmentName,
                        'is_active' => true,
                        'last_synced_at' => now(),
                    ]);
                    $stats['departments_created']++;
                }

                $agreement = AgreementReference::query()->where('contract_number', $contractNumber)->first();
                $payload = [
                    'vendor_id' => $vendor?->id,
                    'division_id' => $division->id,
                    'department_id' => $department->id,
                    'contract_number' => $contractNumber,
                    'title' => cleanImportValue($row['Nama Pengadaan'] ?? null) ?: $contractNumber,
                    'contract_value' => parseImportNumber($row['Total Harga'] ?? null),
                    'effective_date' => parseImportDate($row['Tanggal PO'] ?? null),
                    'created_by' => $createdBy?->id,
                ];

                if ($agreement) {
                    $agreement->forceFill($payload)->save();
                    $stats['agreements_updated']++;
                } else {
                    AgreementReference::query()->create($payload);
                    $stats['agreements_created']++;
                }
            }
        }

        return $stats;
    });

    foreach ($stats as $label => $count) {
        $this->line($label.': '.$count);
    }

    return Command::SUCCESS;
})->purpose('Import temporary e-procurement vendor and purchasing CSV data.');

function readCsvRows(string $path): Generator
{
    if (! is_file($path) || ! is_readable($path)) {
        throw new RuntimeException('CSV tidak bisa dibaca: '.$path);
    }

    $handle = fopen($path, 'rb');
    $headers = null;

    while (($row = fgetcsv($handle)) !== false) {
        if ($headers === null) {
            $headers = array_map(fn ($header) => trim((string) $header), $row);

            continue;
        }

        if ($row === [null] || $row === false) {
            continue;
        }

        yield array_combine($headers, array_slice(array_pad($row, count($headers), null), 0, count($headers)));
    }

    fclose($handle);
}

function cleanImportValue(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim((string) $value);

    return $value === '' ? null : $value;
}

function parseImportNumber(mixed $value): ?string
{
    $value = cleanImportValue($value);

    if ($value === null) {
        return null;
    }

    $number = preg_replace('/[^0-9.-]/', '', $value);

    return $number === '' ? null : number_format((float) $number, 2, '.', '');
}

function parseImportDate(mixed $value): ?string
{
    $value = cleanImportValue($value);

    if ($value === null) {
        return null;
    }

    try {
        return \Carbon\Carbon::parse($value)->toDateString();
    } catch (Throwable) {
        return null;
    }
}

function makeImportCode(string $value): string
{
    return strtoupper(Str::of($value)->slug('_')->limit(30, '')->toString());
}
