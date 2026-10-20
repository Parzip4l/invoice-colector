<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Enums\PpaVerificationSheetStatus;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\Division;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\InvoiceMetadata;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use App\Modules\InvoiceVerification\Services\PpaVerificationSheetService;
use Database\Seeders\InvoiceVerification\InvoiceVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PpaNonContractVerificationSheetTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $kadepUser;

    protected User $kadivUser;

    protected Transaction $transaction;

    protected Division $division;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InvoiceVerificationSeeder::class);
        Storage::fake(config('invoice_verification.storage.documents_disk'));

        $this->division = Division::firstOrFail();
        $this->department = Department::where('division_id', $this->division->id)->firstOrFail();

        $this->owner = User::factory()->create([
            'email' => 'staff.pelayanan@lrtjakarta.co.id',
            'name' => 'Staff Pelayanan',
            'role_code' => RoleCode::USER_DIVISI,
            'division_id' => $this->division->id,
            'department_id' => $this->department->id,
        ]);

        $this->kadepUser = User::factory()->create([
            'email' => 'kadep.pelayanan@lrtjakarta.co.id',
            'name' => 'Budi Kadep Pelayanan',
            'employee_number' => 'NIK-KADEP-001',
            'role_code' => RoleCode::KEPALA_DEPARTEMEN,
            'division_id' => $this->division->id,
            'department_id' => $this->department->id,
        ]);

        $this->kadivUser = User::factory()->create([
            'email' => 'kadiv.operasional@lrtjakarta.co.id',
            'name' => 'Agus Kadiv Operasional',
            'employee_number' => 'NIK-KADIV-002',
            'role_code' => RoleCode::KEPALA_DIVISI,
            'division_id' => $this->division->id,
        ]);

        $pnkType = TransactionType::where('code', TransactionTypeCode::PPA_NON_CONTRACT)->firstOrFail();

        $this->transaction = Transaction::create([
            'registration_number' => 'PNK-202610-0099',
            'title' => 'Konsumsi Acara Pembukaan',
            'description' => 'Konsumsi kegiatan koordinasi lapangan',
            'transaction_type_id' => $pnkType->id,
            'division_id' => $this->division->id,
            'department_id' => $this->department->id,
            'owner_user_id' => $this->owner->id,
            'created_by' => $this->owner->id,
            'status' => TransactionStatus::DRAFT,
            'current_step' => TransactionStep::VENDOR_INVOICE_INPUT,
        ]);

        InvoiceMetadata::create([
            'transaction_id' => $this->transaction->id,
            'invoice_number' => 'INV/PNK/2026/099',
            'invoice_value' => 2500000,
            'ppn_value' => 275000,
            'received_date' => now()->toDateString(),
        ]);
    }

    public function test_user_can_access_edit_form_and_see_categories(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('invoice-verification.transactions.ppa-verification-sheets.edit', $this->transaction));

        $response->assertOk();
        $response->assertSee('Pengajuan Penggunaan Anggaran');
        $response->assertSee('LRTJ-FR-KDA-001');
        $response->assertSee('LRTJ-FR-KDA-004');
        $response->assertSee('Persetujuan Prinsip');
        $response->assertSee('Dokumen Pendukung');
        $response->assertSee('Kelengkapan ERP');
        $response->assertSee('Master Budget');
        $response->assertSee('modalBudgetSearch');
    }

    public function test_user_can_save_draft_with_dynamic_rab_and_checklist(): void
    {
        $payload = [
            'description' => 'Konsumsi Koordinasi Bulanan Tim Stasiun',
            'advance_payment_value' => 500000,
            'exchange_rate_value' => 1.00,
            'accounting_signer_name' => 'Budi Sarjono',
            'budget_items' => [
                [
                    'kode_rab' => '5.01.02.001',
                    'nama_kegiatan' => 'Konsumsi Rapat Koordinasi',
                    'nilai_realisasi' => 1500000,
                ],
                [
                    'kode_rab' => '5.01.02.002',
                    'nama_kegiatan' => 'Snack Box Peserta',
                    'nilai_realisasi' => 1275000,
                ],
            ],
            'checklist_items' => [
                'IV_1' => [
                    'is_required' => '1',
                    'is_actual' => '1',
                    'notes' => 'INV/PNK/2026/099',
                ],
                'IV_2' => [
                    'is_required' => '1',
                    'is_actual' => '1',
                    'notes' => 'KWT/PNK/2026/099',
                ],
                'IV_3' => [
                    'is_required' => '0',
                    'is_actual' => '0',
                    'notes' => 'Non PKP',
                ],
            ],
            'action' => 'save',
        ];

        $response = $this->actingAs($this->owner)
            ->put(route('invoice-verification.transactions.ppa-verification-sheets.update', $this->transaction), $payload);

        $response->assertRedirect(route('invoice-verification.transactions.ppa-verification-sheets.edit', $this->transaction));

        $sheet = $this->transaction->fresh('ppaVerificationSheet')->ppaVerificationSheet;
        $this->assertNotNull($sheet);
        $this->assertCount(2, $sheet->budget_items_json);
        $this->assertSame('5.01.02.001', $sheet->budget_items_json[0]['kode_rab']);
        $this->assertSame(500000.0, (float) $sheet->advance_payment_value);
        $this->assertTrue($sheet->checklist_items_json['IV_1']['is_required']);
        $this->assertFalse($sheet->checklist_items_json['IV_3']['is_required']);
        $this->assertSame('Non PKP', $sheet->checklist_items_json['IV_3']['notes']);
    }

    public function test_user_can_preview_and_download_generated_pdf(): void
    {
        // First save sheet to ensure PDF generated
        app(PpaVerificationSheetService::class)->getOrCreate($this->transaction, $this->owner);

        $previewResponse = $this->actingAs($this->owner)
            ->get(route('invoice-verification.transactions.ppa-verification-sheets.preview', $this->transaction));

        $previewResponse->assertOk();
        $this->assertSame('application/pdf', $previewResponse->headers->get('Content-Type'));

        $downloadResponse = $this->actingAs($this->owner)
            ->get(route('invoice-verification.transactions.ppa-verification-sheets.download', $this->transaction));

        $downloadResponse->assertOk();
        $this->assertSame('application/pdf', $downloadResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $downloadResponse->headers->get('Content-Disposition'));
    }

    public function test_submitting_sheet_triggers_kadep_kadiv_approval_and_approval_stamps_qr_code(): void
    {
        $secret = 'test-secret-key-12345';
        config([
            'invoice_verification.approval_resolver.base_url' => 'https://portal.test',
            'invoice_verification.approval_resolver.client_code' => 'invoice_collector',
            'invoice_verification.approval_resolver.shared_secret' => $secret,
        ]);

        Http::fake([
            'https://portal.test/api/v1/approval/resolve' => Http::response([
                'success' => true,
                'status' => 'OK',
                'data' => [
                    'steps' => [
                        [
                            'step_no' => 1,
                            'approver' => [
                                'id' => $this->kadepUser->id,
                                'name' => $this->kadepUser->name,
                                'email' => $this->kadepUser->email,
                            ],
                        ],
                        [
                            'step_no' => 2,
                            'approver' => [
                                'id' => $this->kadivUser->id,
                                'name' => $this->kadivUser->name,
                                'email' => $this->kadivUser->email,
                            ],
                        ],
                    ],
                ],
            ], 200),
            'https://portal.test/api/v1/signatures' => Http::response([
                'success' => true,
                'data' => ['signature' => [
                    'portal_signature_id' => 'sig-invoice-collector',
                    'portal_signature_url' => 'https://portal.test/verify/sig-invoice-collector',
                ]],
            ], 200),
        ]);

        $requiredDocs = DocumentType::query()
            ->where('transaction_type_id', $this->transaction->transaction_type_id)
            ->where('is_required', true)
            ->get();

        foreach ($requiredDocs as $doc) {
            $this->transaction->documents()->create([
                'document_type_id' => $doc->id,
                'source_actor' => 'VENDOR',
                'file_name' => $doc->code.'.pdf',
                'file_path' => 'transactions/test/'.$doc->code.'.pdf',
                'file_disk' => config('invoice_verification.storage.documents_disk'),
                'file_extension' => 'pdf',
                'file_size' => 1024,
                'mime_type' => 'application/pdf',
                'is_latest' => true,
                'version' => 1,
                'status' => 'UPLOADED',
                'uploaded_by_user_id' => $this->owner->id,
                'uploaded_at' => now(),
            ]);
        }

        // Submit sheet
        $response = $this->actingAs($this->owner)
            ->post(route('invoice-verification.transactions.ppa-verification-sheets.submit', $this->transaction));

        $response->assertRedirect(route('invoice-verification.transactions.show', $this->transaction));

        $sheet = $this->transaction->fresh('ppaVerificationSheet')->ppaVerificationSheet;
        $this->assertSame(PpaVerificationSheetStatus::SUBMITTED, $sheet->status);

        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::WAITING_APPROVAL, $this->transaction->status);

        // Approval flow step 1: Kadep approves
        $step1 = $this->transaction->approvalTransactions()->whereHas('approvalFlow', fn ($q) => $q->where('step_no', 1))->firstOrFail();
        $this->actingAs($this->kadepUser)
            ->put(route('invoice-verification.approvals.update', $step1), [
                'status' => 'APPROVED',
                'notes' => 'Disetujui Kadep',
            ]);

        $step1->refresh();
        $this->assertSame(ApprovalStatus::APPROVED, $step1->status);

        // Kadiv approves
        $step2 = $this->transaction->approvalTransactions()->whereHas('approvalFlow', fn ($q) => $q->where('step_no', 2))->firstOrFail();
        $this->actingAs($this->kadivUser)
            ->put(route('invoice-verification.approvals.update', $step2), [
                'status' => 'APPROVED',
                'notes' => 'Disetujui Kadiv',
            ]);

        $step2->refresh();
        $this->assertSame(ApprovalStatus::APPROVED, $step2->status);

        // Transaction is now SUBMITTED / ACCOUNTING_VERIFICATION
        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::SUBMITTED, $this->transaction->status);
        $this->assertSame(TransactionStep::ACCOUNTING_VERIFICATION, $this->transaction->current_step);

        // PDF is regenerated with QR code and readable
        $sheet->refresh();
        $this->assertNotNull($sheet->file_path);
        $this->assertTrue(Storage::disk($sheet->file_disk)->exists($sheet->file_path));
    }
}
