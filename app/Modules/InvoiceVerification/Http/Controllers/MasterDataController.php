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

        $user = auth()->user();
        $memoRequests = MemoRequest::query()
            ->with(['creator', 'division', 'department'])
            ->when(! $user?->hasRole(RoleCode::AKUNTANSI), function ($query) use ($user) {
                $query->where('division_id', $user?->division_id)
                    ->where('department_id', $user?->department_id);
            })
            ->latest('memo_date')
            ->get();

        $agreementReferences = AgreementReference::query()
            ->with(['creator', 'division', 'department', 'vendor'])
            ->when(! $user?->hasRole(RoleCode::AKUNTANSI), function ($query) use ($user) {
                $query->where('division_id', $user?->division_id)
                    ->where('department_id', $user?->department_id);
            })
            ->orderBy('contract_number')
            ->get();

        return view('invoice-verification.master-data.index', [
            'banks' => Bank::query()->orderBy('name')->get(),
            'vendors' => Vendor::query()->with('defaultBank')->orderBy('name')->get(),
            'internalUsers' => User::query()
                ->with(['division', 'department'])
                ->where('role_code', '!=', RoleCode::VENDOR->value)
                ->orderBy('name')
                ->get(),
            'roleOptions' => collect(RoleCode::cases())
                ->reject(fn (RoleCode $role) => $role === RoleCode::VENDOR)
                ->values(),
            'memoRequests' => $memoRequests,
            'agreementReferences' => $agreementReferences,
            'templateReferences' => TemplateReference::query()->orderBy('code')->get(),
            'transactionTypes' => TransactionType::query()->orderBy('name')->get(),
            'divisions' => Division::query()->withCount('departments')->orderBy('name')->get(),
            'departments' => Department::query()->with('division')->orderBy('name')->get(),
        ]);
    }

    public function storeBank(StoreBankRequest $request)
    {
        Bank::create($request->validated());

        return back()->with('success', 'Master bank berhasil ditambahkan.');
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

        return back()->with('success', 'Master vendor berhasil ditambahkan.');
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

        return back()->with('success', 'Master vendor berhasil diperbarui.');
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

        return back()->with('success', 'Whitelist LDAP berhasil disimpan.');
    }

    public function updateLdapWhitelist(Request $request, User $user)
    {
        $this->authorize('manageMasterData', Transaction::class);

        abort_if($user->hasRole(RoleCode::VENDOR), 404);

        $payload = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->forceFill([
            'is_active' => (bool) $payload['is_active'],
        ])->save();

        return back()->with('success', 'Status whitelist LDAP berhasil diperbarui.');
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

        return back()->with('success', 'Master divisi berhasil disimpan.');
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

        return back()->with('success', 'Master divisi berhasil diperbarui.');
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

        return back()->with('success', 'Master department berhasil disimpan.');
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

        return back()->with('success', 'Master department berhasil diperbarui.');
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

        return back()->with('success', 'Memo permohonan berhasil ditambahkan dan file sudah diunggah.');
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

        return back()->with('success', 'Referensi kontrak berhasil ditambahkan dan dapat dipilih kembali untuk tagihan berikutnya.');
    }

    public function storeTemplate(StoreTemplateReferenceRequest $request)
    {
        TemplateReference::create($request->validated());

        return back()->with('success', 'Template reference berhasil ditambahkan.');
    }

    public function syncLdap()
    {
        $this->authorize('manageMasterData', Transaction::class);

        $result = $this->ldapDirectorySynchronizer->syncAll();

        return back()->with('success', $result['message']);
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
