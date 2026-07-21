<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\Bank;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\Division;
use App\Modules\InvoiceVerification\Domain\Models\MemoRequest;
use App\Modules\InvoiceVerification\Domain\Models\TemplateReference;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use App\Modules\InvoiceVerification\Http\Requests\StoreAgreementReferenceRequest;
use App\Modules\InvoiceVerification\Http\Requests\StoreBankRequest;
use App\Modules\InvoiceVerification\Http\Requests\StoreMemoRequest;
use App\Modules\InvoiceVerification\Http\Requests\StoreTemplateReferenceRequest;
use App\Modules\InvoiceVerification\Http\Requests\StoreVendorRequest;
use App\Modules\InvoiceVerification\Services\AuditLogService;
use App\Modules\InvoiceVerification\Services\Contracts\LdapDirectorySynchronizer;
use App\Modules\InvoiceVerification\Services\Eproc\EprocImportService;
use App\Modules\InvoiceVerification\Services\Eproc\SpreadsheetImportReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterDataController extends Controller
{
    public function __construct(
        protected LdapDirectorySynchronizer $ldapDirectorySynchronizer,
        protected AuditLogService $auditLogService,
    ) {
    }

    public function index()
    {
        $this->authorize('manageMasterData', Transaction::class);

        $activeTab = in_array(request('tab'), ['vendors', 'organization', 'ldap', 'memo', 'agreements', 'templates'], true)
            ? request('tab')
            : 'vendors';
        $search = Str::lower(trim((string) request('q', '')));
        $status = (string) request('status', '');
        $type = (string) request('type', '');
        $perPage = 25;
        $filterSearch = function ($query, array $columns) use ($search) {
            if ($search === '') {
                return;
            }

            $query->where(function ($nested) use ($columns, $search) {
                foreach ($columns as $column) {
                    $nested->orWhereRaw('LOWER('.$column.') LIKE ?', ['%'.$search.'%']);
                }
            });
        };

        $memoRequests = MemoRequest::query()
            ->with(['creator', 'division', 'department'])
            ->when($activeTab === 'memo', function ($query) use ($filterSearch, $status) {
                $filterSearch($query, ['memo_number', 'subject']);
                $query->when($status === 'active', fn ($query) => $query->whereNotNull('file_path'));
                $query->when($status === 'pending', fn ($query) => $query->whereNull('file_path'));
            })
            ->latest('memo_date')
            ->paginate($perPage, ['*'], 'memo_page')
            ->withQueryString();

        $agreementReferences = AgreementReference::query()
            ->with(['creator', 'division', 'department', 'vendor'])
            ->when($activeTab === 'agreements', function ($query) use ($filterSearch, $status) {
                $filterSearch($query, ['contract_number', 'title']);
                $query->when($status === 'active', fn ($query) => $query->whereNotNull('file_path'));
                $query->when($status === 'pending', fn ($query) => $query->whereNull('file_path'));
            })
            ->orderBy('contract_number')
            ->paginate($perPage, ['*'], 'agreements_page')
            ->withQueryString();

        $vendors = Vendor::query()
            ->with('defaultBank')
            ->when($activeTab === 'vendors', function ($query) use ($filterSearch, $status) {
                $filterSearch($query, ['name', 'npwp', 'contact_name', 'contact_email']);
                $query->when($status === 'inactive', fn ($query) => $query->whereRaw('1 = 0'));
            })
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'vendors_page')
            ->withQueryString();

        $internalUsers = User::query()
            ->with(['division', 'department'])
            ->where('role_code', '!=', RoleCode::VENDOR->value)
            ->when($activeTab === 'ldap', function ($query) use ($filterSearch, $status) {
                $filterSearch($query, ['name', 'email', 'ldap_uid', 'employee_number']);
                $query->when($status === 'active', fn ($query) => $query->where('is_active', true));
                $query->when($status === 'inactive', fn ($query) => $query->where('is_active', false));
            })
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'ldap_page')
            ->withQueryString();

        $divisions = Division::query()
            ->withCount('departments')
            ->when($activeTab === 'organization', function ($query) use ($filterSearch, $status, $type) {
                $filterSearch($query, ['name', 'ldap_code']);
                $query->when($status === 'active', fn ($query) => $query->where('is_active', true));
                $query->when($status === 'inactive', fn ($query) => $query->where('is_active', false));
                $query->when($type !== '' && $type !== 'division', fn ($query) => $query->whereRaw('1 = 0'));
            })
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'divisions_page')
            ->withQueryString();

        $departments = Department::query()
            ->with('division')
            ->when($activeTab === 'organization', function ($query) use ($filterSearch, $status, $type) {
                $filterSearch($query, ['departments.name', 'departments.ldap_code']);
                $query->when($status === 'active', fn ($query) => $query->where('departments.is_active', true));
                $query->when($status === 'inactive', fn ($query) => $query->where('departments.is_active', false));
                $query->when($type !== '' && $type !== 'department', fn ($query) => $query->whereRaw('1 = 0'));
            })
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'departments_page')
            ->withQueryString();

        $templateReferences = TemplateReference::query()
            ->when($activeTab === 'templates', function ($query) use ($filterSearch, $status) {
                $filterSearch($query, ['code', 'name', 'document_code']);
                $query->when($status === 'active', fn ($query) => $query->where('is_active', true));
                $query->when($status === 'inactive', fn ($query) => $query->where('is_active', false));
            })
            ->orderBy('code')
            ->paginate($perPage, ['*'], 'templates_page')
            ->withQueryString();

        return view('invoice-verification.master-data.index', [
            'banks' => Bank::query()->orderBy('name')->get(),
            'vendors' => $vendors,
            'vendorOptions' => Vendor::query()->orderBy('name')->get(['id', 'name']),
            'internalUsers' => $internalUsers,
            'roleOptions' => collect(RoleCode::cases())
                ->reject(fn (RoleCode $role) => $role === RoleCode::VENDOR)
                ->values(),
            'memoRequests' => $memoRequests,
            'agreementReferences' => $agreementReferences,
            'templateReferences' => $templateReferences,
            'transactionTypes' => TransactionType::query()->orderBy('name')->get(),
            'divisions' => $divisions,
            'departmentOptions' => Department::query()->with('division')->orderBy('name')->get(),
            'divisionOptions' => Division::query()->orderBy('name')->get(),
            'departments' => $departments,
            'activeTab' => $activeTab,
            'search' => $search,
            'statusFilter' => $status,
            'typeFilter' => $type,
            'vendorTotal' => Vendor::query()->count(),
            'internalUserTotal' => User::query()->where('role_code', '!=', RoleCode::VENDOR->value)->count(),
            'activeDivisionTotal' => Division::query()->where('is_active', true)->count(),
            'activeDepartmentTotal' => Department::query()->where('is_active', true)->count(),
            'activeTemplateTotal' => TemplateReference::query()->where('is_active', true)->count(),
        ]);
    }

    private function redirectToMasterData(string $tab, string $message)
    {
        return redirect()
            ->route('invoice-verification.master-data.index', ['tab' => $tab])
            ->with('success', $message);
    }

    public function storeBank(StoreBankRequest $request)
    {
        Bank::create($request->validated());

        return $this->redirectToMasterData('vendors', 'Master bank berhasil ditambahkan.');
    }

    public function storeVendor(StoreVendorRequest $request)
    {
        $bank = null;
        $contactEmail = Str::lower((string) $request->validated('contact_email', ''));

        if ($contactEmail !== '') {
            $existingUser = User::query()
                ->whereRaw('LOWER(email) = ?', [$contactEmail])
                ->first();

            if ($existingUser && ! $existingUser->hasRole(RoleCode::VENDOR)) {
                throw ValidationException::withMessages([
                    'contact_email' => 'Email ini sudah terdaftar sebagai user internal, tidak bisa dipakai untuk akun vendor.',
                ]);
            }
        }

        if ($request->filled('bank_name')) {
            $bank = Bank::firstOrCreate(
                ['name' => $request->string('bank_name')->trim()->toString()],
                [
                    'code' => strtoupper(str($request->string('bank_name')->trim()->toString())->slug('_')->limit(20, '')),
                ],
            );
        }

        $vendor = Vendor::create([
            'name' => $request->validated('name'),
            'npwp' => $request->validated('npwp'),
            'contact_name' => $request->validated('contact_name'),
            'contact_email' => $contactEmail ?: null,
            'contact_phone' => $request->validated('contact_phone'),
            'default_bank_id' => $bank?->id,
            'default_account_number' => $request->validated('default_account_number'),
        ]);

        if ($contactEmail !== '') {
            User::updateOrCreate(
                ['email' => $contactEmail],
                [
                    'name' => $request->validated('contact_name') ?: $vendor->name,
                    'ldap_uid' => null,
                    'employee_number' => null,
                    'role_code' => RoleCode::VENDOR->value,
                    'division_id' => $request->user()?->division_id,
                    'department_id' => $request->user()?->department_id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make((string) $request->validated('vendor_password')),
                ],
            );
        }

        return $this->redirectToMasterData('vendors', 'Master vendor berhasil ditambahkan.');
    }

    public function updateVendor(Request $request, Vendor $vendor)
    {
        $this->authorize('manageMasterData', Transaction::class);

        if ($request->filled('default_account_number')) {
            $request->merge(['default_account_number' => preg_replace('/[\s-]+/', '', (string) $request->input('default_account_number'))]);
        }

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'vendor_password' => ['nullable', 'string', 'min:8'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'default_account_number' => ['nullable', 'regex:/^\d{6,30}$/'],
        ], [
            'default_account_number.regex' => 'Nomor rekening hanya boleh berisi angka, 6 sampai 30 digit.',
        ]);

        $contactEmail = Str::lower((string) ($payload['contact_email'] ?? ''));

        if ($contactEmail !== '') {
            $existingUser = User::query()
                ->whereRaw('LOWER(email) = ?', [$contactEmail])
                ->first();

            if ($existingUser && ! $existingUser->hasRole(RoleCode::VENDOR)) {
                throw ValidationException::withMessages([
                    'contact_email' => 'Email ini sudah terdaftar sebagai user internal, tidak bisa dipakai untuk akun vendor.',
                ]);
            }
        }

        $bank = null;

        if ($request->filled('bank_name')) {
            $bank = Bank::firstOrCreate(
                ['name' => $request->string('bank_name')->trim()->toString()],
                [
                    'code' => strtoupper(str($request->string('bank_name')->trim()->toString())->slug('_')->limit(20, '')),
                ],
            );
        }

        $vendor->update([
            'name' => $payload['name'],
            'npwp' => $payload['npwp'] ?? null,
            'contact_name' => $payload['contact_name'] ?? null,
            'contact_email' => $contactEmail ?: null,
            'contact_phone' => $payload['contact_phone'] ?? null,
            'default_bank_id' => $bank?->id,
            'default_account_number' => $payload['default_account_number'] ?? null,
        ]);

        if ($contactEmail !== '') {
            $userAttributes = [
                'name' => ($payload['contact_name'] ?? null) ?: $vendor->name,
                'ldap_uid' => null,
                'employee_number' => null,
                'role_code' => RoleCode::VENDOR->value,
                'division_id' => $request->user()?->division_id,
                'department_id' => $request->user()?->department_id,
                'is_active' => true,
                'email_verified_at' => now(),
            ];

            if (! empty($payload['vendor_password'])) {
                $userAttributes['password'] = Hash::make((string) $payload['vendor_password']);
            }

            $vendorUser = User::query()
                ->whereRaw('LOWER(email) = ?', [$contactEmail])
                ->first();

            if ($vendorUser) {
                $vendorUser->forceFill($userAttributes)->save();
            } else {
                User::forceCreate([
                    'email' => $contactEmail,
                    'password' => Hash::make((string) ($payload['vendor_password'] ?? Str::random(40))),
                    ...$userAttributes,
                ]);
            }
        }

        return $this->redirectToMasterData('vendors', 'Master vendor berhasil diperbarui.');
    }

    public function storeLdapWhitelist(Request $request)
    {
        $this->authorize('manageMasterData', Transaction::class);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'ldap_uid' => ['nullable', 'string', 'max:255'],
            'employee_number' => ['nullable', 'string', 'max:255'],
            'role_code' => [
                'required',
                Rule::in(
                    collect(RoleCode::cases())
                        ->reject(fn (RoleCode $role) => $role === RoleCode::VENDOR)
                        ->map(fn (RoleCode $role) => $role->value)
                        ->all(),
                ),
            ],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $email = Str::lower($payload['email']);
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user && $user->hasRole(RoleCode::VENDOR)) {
            throw ValidationException::withMessages([
                'email' => 'Email ini sudah terdaftar sebagai vendor, tidak bisa dipakai untuk whitelist LDAP internal.',
            ]);
        }

        $attributes = [
            'name' => $payload['name'],
            'ldap_uid' => $payload['ldap_uid'] ?? null,
            'employee_number' => $payload['employee_number'] ?? null,
            'role_code' => $payload['role_code'],
            'division_id' => $payload['division_id'] ?? null,
            'department_id' => $payload['department_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
            'last_synced_at' => now(),
        ];

        if ($user) {
            $user->forceFill($attributes)->save();
        } else {
            User::forceCreate([
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                ...$attributes,
            ]);
        }

        return $this->redirectToMasterData('ldap', 'Whitelist LDAP berhasil disimpan.');
    }

    public function updateLdapWhitelist(Request $request, User $user)
    {
        $this->authorize('manageMasterData', Transaction::class);

        abort_if($user->hasRole(RoleCode::VENDOR), 404);

        if ($request->hasAny(['name', 'email', 'role_code', 'division_id', 'department_id', 'ldap_uid', 'employee_number'])) {
            $payload = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'ldap_uid' => ['nullable', 'string', 'max:255'],
                'employee_number' => ['nullable', 'string', 'max:255'],
                'role_code' => [
                    'required',
                    Rule::in(
                        collect(RoleCode::cases())
                            ->reject(fn (RoleCode $role) => $role === RoleCode::VENDOR)
                            ->map(fn (RoleCode $role) => $role->value)
                            ->all(),
                    ),
                ],
                'division_id' => ['nullable', 'exists:divisions,id'],
                'department_id' => ['nullable', 'exists:departments,id'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $user->forceFill([
                'name' => $payload['name'],
                'email' => Str::lower($payload['email']),
                'ldap_uid' => $payload['ldap_uid'] ?? null,
                'employee_number' => $payload['employee_number'] ?? null,
                'role_code' => $payload['role_code'],
                'division_id' => $payload['division_id'] ?? null,
                'department_id' => $payload['department_id'] ?? null,
                'is_active' => $request->boolean('is_active'),
                'last_synced_at' => now(),
            ])->save();

            return $this->redirectToMasterData('ldap', 'Whitelist LDAP berhasil diperbarui.');
        }

        $payload = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->forceFill([
            'is_active' => (bool) $payload['is_active'],
        ])->save();

        return $this->redirectToMasterData('ldap', 'Status whitelist LDAP berhasil diperbarui.');
    }

    public function storeDivision(Request $request)
    {
        $this->authorize('manageMasterData', Transaction::class);

        $payload = $request->validate([
            'ldap_code' => ['nullable', 'string', 'max:255', 'unique:divisions,ldap_code'],
            'name' => ['required', 'string', 'max:255'],
            'petty_cash_ceiling' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Division::create([
            'ldap_code' => $payload['ldap_code'] ?? null,
            'name' => $payload['name'],
            'petty_cash_ceiling' => $payload['petty_cash_ceiling'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->redirectToMasterData('organization', 'Master divisi berhasil disimpan.');
    }

    public function updateDivision(Request $request, Division $division)
    {
        $this->authorize('manageMasterData', Transaction::class);

        $payload = $request->validate([
            'ldap_code' => ['nullable', 'string', 'max:255', Rule::unique('divisions', 'ldap_code')->ignore($division->id)],
            'name' => ['required', 'string', 'max:255'],
            'petty_cash_ceiling' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $division->update([
            'ldap_code' => $payload['ldap_code'] ?? null,
            'name' => $payload['name'],
            'petty_cash_ceiling' => $payload['petty_cash_ceiling'] ?? null,
            'is_active' => (bool) $payload['is_active'],
        ]);

        return $this->redirectToMasterData('organization', 'Master divisi berhasil diperbarui.');
    }

    public function storeDepartment(Request $request)
    {
        $this->authorize('manageMasterData', Transaction::class);

        $payload = $request->validate([
            'division_id' => ['required', 'exists:divisions,id'],
            'ldap_code' => ['nullable', 'string', 'max:255', 'unique:departments,ldap_code'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Department::create([
            'division_id' => $payload['division_id'],
            'ldap_code' => $payload['ldap_code'] ?? null,
            'name' => $payload['name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->redirectToMasterData('organization', 'Master department berhasil disimpan.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $this->authorize('manageMasterData', Transaction::class);

        $payload = $request->validate([
            'division_id' => ['required', 'exists:divisions,id'],
            'ldap_code' => ['nullable', 'string', 'max:255', Rule::unique('departments', 'ldap_code')->ignore($department->id)],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $department->update([
            'division_id' => $payload['division_id'],
            'ldap_code' => $payload['ldap_code'] ?? null,
            'name' => $payload['name'],
            'is_active' => (bool) $payload['is_active'],
        ]);

        return $this->redirectToMasterData('organization', 'Master department berhasil diperbarui.');
    }

    public function storeMemo(StoreMemoRequest $request)
    {
        $uploadedFile = $request->file('memo_file');
        $payload = $request->safe()->except('memo_file');
        $disk = config('invoice_verification.storage.documents_disk', 'public');
        $path = $uploadedFile->store('master-data/memo-requests', $disk);

        $memoRequest = MemoRequest::create([
            ...$payload,
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $path,
            'file_disk' => $disk,
            'file_extension' => $uploadedFile->getClientOriginalExtension(),
            'mime_type' => $uploadedFile->getMimeType(),
            'file_size' => $uploadedFile->getSize(),
            'uploaded_at' => now(),
            'created_by' => $request->user()->id,
        ]);

        $this->auditLogService->log(
            module: 'memo-requests',
            action: 'upload_file',
            actor: $request->user(),
            referenceType: MemoRequest::class,
            referenceId: $memoRequest->id,
            newValue: [
                'memo_number' => $memoRequest->memo_number,
                'file_name' => $memoRequest->file_name,
            ],
        );

        return $this->redirectToMasterData('memo', 'Memo permohonan berhasil ditambahkan dan file sudah diunggah.');
    }

    public function storeAgreement(StoreAgreementReferenceRequest $request)
    {
        $uploadedFile = $request->file('agreement_file');
        $payload = $request->safe()->except('agreement_file');
        $disk = config('invoice_verification.storage.documents_disk', 'public');
        $path = $uploadedFile->store('master-data/agreement-references', $disk);

        $agreementReference = AgreementReference::create([
            ...$payload,
            'title' => $request->validated('contract_number'),
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $path,
            'file_disk' => $disk,
            'file_extension' => $uploadedFile->getClientOriginalExtension(),
            'mime_type' => $uploadedFile->getMimeType(),
            'file_size' => $uploadedFile->getSize(),
            'uploaded_at' => now(),
            'created_by' => $request->user()->id,
        ]);

        $this->auditLogService->log(
            module: 'agreement-references',
            action: 'upload_file',
            actor: $request->user(),
            referenceType: AgreementReference::class,
            referenceId: $agreementReference->id,
            newValue: [
                'contract_number' => $agreementReference->contract_number,
                'file_name' => $agreementReference->file_name,
            ],
        );

        return $this->redirectToMasterData('agreements', 'Referensi kontrak berhasil ditambahkan dan dapat dipilih kembali untuk tagihan berikutnya.');
    }

    public function storeTemplate(StoreTemplateReferenceRequest $request)
    {
        TemplateReference::create($request->validated());

        return $this->redirectToMasterData('templates', 'Template reference berhasil ditambahkan.');
    }

    public function syncLdap()
    {
        $this->authorize('manageMasterData', Transaction::class);

        $result = $this->ldapDirectorySynchronizer->syncAll();

        return $this->redirectToMasterData('ldap', $result['message']);
    }

    public function importEproc(Request $request, SpreadsheetImportReader $reader, EprocImportService $importer)
    {
        $this->authorize('manageMasterData', Transaction::class);

        $payload = $request->validate([
            'vendor_file' => ['nullable', 'required_without:purchasing_file', 'file', 'mimes:csv,txt,xlsx', 'max:20480'],
            'purchasing_file' => ['nullable', 'file', 'mimes:csv,txt,xlsx', 'max:20480'],
            'division_code' => ['nullable', 'string', 'max:255'],
            'division_name' => ['nullable', 'string', 'max:255'],
        ]);

        $vendorRows = [];
        $purchasingRows = [];

        if ($request->hasFile('vendor_file')) {
            $vendorFile = $request->file('vendor_file');
            $vendorRows = $reader->read($vendorFile->getRealPath(), $vendorFile->getClientOriginalExtension());
        }

        if ($request->hasFile('purchasing_file')) {
            $purchasingFile = $request->file('purchasing_file');
            $purchasingRows = $reader->read($purchasingFile->getRealPath(), $purchasingFile->getClientOriginalExtension());
        }

        $stats = $importer->import(
            vendorRows: $vendorRows,
            purchasingRows: $purchasingRows,
            createdBy: $request->user(),
            divisionCode: $payload['division_code'] ?? 'EPROC',
            divisionName: $payload['division_name'] ?? 'E-Procurement',
        );

        $targetTab = $request->hasFile('purchasing_file') ? 'agreements' : 'vendors';

        return $this->redirectToMasterData($targetTab, sprintf(
            'Import E-Proc selesai. Vendor baru %d, vendor update %d, PO baru %d, PO update %d, department baru %d.',
            $stats['vendors_created'],
            $stats['vendors_updated'],
            $stats['agreements_created'],
            $stats['agreements_updated'],
            $stats['departments_created'],
        ));
    }

    public function downloadMemo(MemoRequest $memoRequest): StreamedResponse
    {
        $this->authorize('viewAny', Transaction::class);

        abort_unless($memoRequest->file_path && $memoRequest->file_disk, 404);

        return Storage::disk($memoRequest->file_disk)->download(
            $memoRequest->file_path,
            $memoRequest->file_name ?? basename($memoRequest->file_path),
        );
    }

    public function previewMemo(MemoRequest $memoRequest): StreamedResponse
    {
        $this->authorize('viewAny', Transaction::class);

        abort_unless($memoRequest->file_path && $memoRequest->file_disk, 404);
        abort_unless(Storage::disk($memoRequest->file_disk)->exists($memoRequest->file_path), 404);

        $fileName = $memoRequest->file_name ?? basename($memoRequest->file_path);
        $mimeType = Storage::disk($memoRequest->file_disk)->mimeType($memoRequest->file_path);
        if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) === 'pdf') {
            $mimeType = 'application/pdf';
        }

        return Storage::disk($memoRequest->file_disk)->response(
            $memoRequest->file_path,
            $fileName,
            [
                'Content-Type' => $mimeType ?: 'application/octet-stream',
            ],
            'inline',
        );
    }

    public function downloadAgreement(AgreementReference $agreementReference): StreamedResponse
    {
        $this->authorize('viewAny', Transaction::class);

        abort_unless($agreementReference->file_path && $agreementReference->file_disk, 404);

        return Storage::disk($agreementReference->file_disk)->download(
            $agreementReference->file_path,
            $agreementReference->file_name ?? basename($agreementReference->file_path),
        );
    }

    public function previewAgreement(AgreementReference $agreementReference): StreamedResponse
    {
        $this->authorize('viewAny', Transaction::class);

        abort_unless($agreementReference->file_path && $agreementReference->file_disk, 404);
        abort_unless(Storage::disk($agreementReference->file_disk)->exists($agreementReference->file_path), 404);

        $fileName = $agreementReference->file_name ?? basename($agreementReference->file_path);
        $mimeType = Storage::disk($agreementReference->file_disk)->mimeType($agreementReference->file_path);
        if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) === 'pdf') {
            $mimeType = 'application/pdf';
        }

        return Storage::disk($agreementReference->file_disk)->response(
            $agreementReference->file_path,
            $fileName,
            [
                'Content-Type' => $mimeType ?: 'application/octet-stream',
            ],
            'inline',
        );
    }
}
