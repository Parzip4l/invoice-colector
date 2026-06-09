<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\Storage;
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
            'memoRequests' => $memoRequests,
            'agreementReferences' => $agreementReferences,
            'templateReferences' => TemplateReference::query()->orderBy('code')->get(),
            'transactionTypes' => TransactionType::query()->orderBy('name')->get(),
            'divisions' => Division::query()->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
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

        if ($request->filled('bank_name')) {
            $bank = Bank::firstOrCreate(
                ['name' => $request->string('bank_name')->trim()->toString()],
                [
                    'code' => strtoupper(str($request->string('bank_name')->trim()->toString())->slug('_')->limit(20, '')),
                ],
            );
        }

        Vendor::create([
            'name' => $request->validated('name'),
            'npwp' => $request->validated('npwp'),
            'default_bank_id' => $bank?->id,
            'default_account_number' => $request->validated('default_account_number'),
        ]);

        return back()->with('success', 'Master vendor berhasil ditambahkan.');
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

    public function downloadAgreement(AgreementReference $agreementReference): StreamedResponse
    {
        $this->authorize('viewAny', Transaction::class);

        abort_unless($agreementReference->file_path && $agreementReference->file_disk, 404);

        return Storage::disk($agreementReference->file_disk)->download(
            $agreementReference->file_path,
            $agreementReference->file_name ?? basename($agreementReference->file_path),
        );
    }
}
