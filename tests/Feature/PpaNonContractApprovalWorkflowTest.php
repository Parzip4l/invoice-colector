<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\Division;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use Database\Seeders\InvoiceVerification\InvoiceVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PpaNonContractApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $uploader;

    protected User $kadep;

    protected User $kadiv;

    protected User $accounting;

    protected Transaction $transaction;

    protected TransactionType $ppaNonContractType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InvoiceVerificationSeeder::class);

        $division = Division::firstOrFail();
        $department = Department::where('division_id', $division->id)->firstOrFail();
        $vendor = Vendor::firstOrFail();

        $this->owner = User::factory()->create([
            'name' => 'Owner PPA Non Kontrak',
            'email' => 'owner.ppa@lrtjakarta.co.id',
            'role_code' => RoleCode::USER_DIVISI,
            'division_id' => $division->id,
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $this->uploader = User::factory()->create([
            'name' => 'Uploader Khusus',
            'email' => 'uploader.accidental@lrtjakarta.co.id',
            'role_code' => RoleCode::USER_DIVISI,
            'division_id' => $division->id,
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $this->kadep = User::factory()->create([
            'name' => 'Kepala Departemen Test',
            'email' => 'kadep.test@lrtjakarta.co.id',
            'role_code' => RoleCode::KEPALA_DEPARTEMEN,
            'division_id' => $division->id,
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $this->kadiv = User::factory()->create([
            'name' => 'Kepala Divisi Test',
            'email' => 'kadiv.test@lrtjakarta.co.id',
            'role_code' => RoleCode::KEPALA_DIVISI,
            'division_id' => $division->id,
            'department_id' => null,
            'is_active' => true,
        ]);

        $this->accounting = User::factory()->create([
            'name' => 'Akuntansi Verifier',
            'email' => 'akuntansi.verifier@lrtjakarta.co.id',
            'role_code' => RoleCode::AKUNTANSI,
            'is_active' => true,
        ]);

        $this->ppaNonContractType = TransactionType::where('code', TransactionTypeCode::PPA_NON_CONTRACT->value)->firstOrFail();

        $this->transaction = Transaction::create([
            'transaction_type_id' => $this->ppaNonContractType->id,
            'registration_number' => 'REG-PNK-2026-TEST',
            'title' => 'Pengadaan Sparepart Non Kontrak',
            'description' => 'Transaksi PPA Non Kontrak Uji Coba',
            'status' => TransactionStatus::DRAFT,
            'current_step' => TransactionStep::INTERNAL_DOCUMENT_UPLOAD,
            'owner_user_id' => $this->owner->id,
            'created_by' => $this->owner->id,
            'division_id' => $division->id,
            'department_id' => $department->id,
            'vendor_id' => $vendor->id,
        ]);

        config([
            'invoice_verification.approval_resolver.base_url' => 'https://portal.lrtjakarta.co.id',
            'invoice_verification.approval_resolver.endpoint' => '/api/v1/approval/resolve',
            'invoice_verification.approval_resolver.client_code' => 'invoice_collector',
            'invoice_verification.approval_resolver.shared_secret' => 'test-secret-key-123',
            'invoice_verification.approval_resolver.verify_ssl' => true,
            'invoice_verification.portal_api.shared_secret' => 'test-secret-key-123',
        ]);
    }

    public function test_approval_not_triggered_until_all_required_documents_uploaded(): void
    {
        $requiredDocs = DocumentType::query()
            ->where('transaction_type_id', $this->ppaNonContractType->id)
            ->where('is_required', true)
            ->get();

        // Upload only the first required document
        $firstDoc = $requiredDocs->first();

        $this->actingAs($this->owner)
            ->post(route('invoice-verification.transactions.documents.combined.store', $this->transaction), [
                'attachments' => [
                    [
                        'document_type_id' => $firstDoc->id,
                        'source_actor' => 'VENDOR',
                        'document_label' => $firstDoc->name,
                        'file' => UploadedFile::fake()->create('doc1.pdf', 100, 'application/pdf'),
                        'document_information' => [
                            'document_number' => 'DOC-001',
                            'document_date' => '2026-10-20',
                            'dpp_value' => 10000000,
                            'ppn_value' => 1100000,
                        ],
                    ],
                ],
            ]);

        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::DRAFT, $this->transaction->status);
        $this->assertCount(0, $this->transaction->approvalTransactions);
    }

    public function test_uploading_all_required_documents_triggers_approval_with_owner_email_and_signed_portal_request(): void
    {
        $secret = 'test-secret-key-123';
        $capturedRequest = null;

        Http::fake([
            'https://portal.lrtjakarta.co.id/api/v1/approval/resolve' => function ($request) use (&$capturedRequest) {
                $payload = json_decode($request->body(), true);
                if (data_get($payload, 'metadata.purpose') === 'position_lookup') {
                    return Http::response([
                        'success' => true,
                        'data' => ['position_holder' => [
                            'id' => 'portal-finance-head',
                            'name' => 'Adityawarman Nugroho',
                            'email' => 'adityawarman.nugroho@lrtjakarta.co.id',
                            'employee_number' => 'EMP-FIN-001',
                            'position' => 'Finance & Accounting Division Head',
                        ]],
                    ], 200);
                }

                $capturedRequest = $request;

                return Http::response([
                    'success' => true,
                    'message' => 'Approval resolved successfully.',
                    'data' => [
                        'steps' => [
                            [
                                'step_order' => 1,
                                'step_type' => 'approval',
                                'approver' => [
                                    'name' => 'Kepala Departemen Test',
                                    'email' => 'kadep.test@lrtjakarta.co.id',
                                    'employee_number' => 'EMP-KADEP',
                                ],
                            ],
                            [
                                'step_order' => 2,
                                'step_type' => 'approval',
                                'approver' => [
                                    'name' => 'Kepala Divisi Test',
                                    'email' => 'kadiv.test@lrtjakarta.co.id',
                                    'employee_number' => 'EMP-KADIV',
                                ],
                            ],
                        ],
                    ],
                ], 200);
            },
            'https://portal.lrtjakarta.co.id/api/v1/signatures' => Http::response([
                'success' => true,
                'data' => [
                    'signature' => [
                        'portal_signature_id' => 'sig-invoice-collector-test',
                        'portal_signature_url' => 'https://portal.lrtjakarta.co.id/verify/sig-invoice-collector-test',
                    ],
                ],
            ], 200),
        ]);

        $requiredDocs = DocumentType::query()
            ->where('transaction_type_id', $this->ppaNonContractType->id)
            ->where('is_required', true)
            ->get();

        $attachments = [];
        foreach ($requiredDocs as $doc) {
            $attachments[] = [
                'document_type_id' => $doc->id,
                'source_actor' => 'VENDOR',
                'document_label' => $doc->name,
                'file' => UploadedFile::fake()->create($doc->code.'.pdf', 100, 'application/pdf'),
                'document_information' => [
                    'document_number' => 'NUM-'.$doc->code,
                    'document_date' => '2026-10-20',
                    'dpp_value' => 15000000,
                    'ppn_value' => 1650000,
                ],
            ];
        }

        // Upload using $this->uploader (NOT the transaction owner)
        // Must still use $this->owner->email in the resolver call!
        $this->actingAs($this->owner)
            ->post(route('invoice-verification.transactions.documents.combined.store', $this->transaction), [
                'attachments' => $attachments,
            ])
            ->assertRedirect(route('invoice-verification.transactions.ppa-verification-sheets.edit', $this->transaction));

        // Submit PPA verification sheet to trigger Kadep & Kadiv approval
        $this->actingAs($this->owner)
            ->post(route('invoice-verification.transactions.ppa-verification-sheets.submit', $this->transaction))
            ->assertRedirect(route('invoice-verification.transactions.show', $this->transaction));

        $this->assertNotNull($capturedRequest, 'HTTP request to Portal approval resolver must be made.');

        // 1. Check client code is invoice_collector (NOT satset)
        $this->assertSame('invoice_collector', $capturedRequest->header('X-App-Code')[0]);

        // 2. Check requester email is owner->email (NOT uploader)
        $payload = json_decode($capturedRequest->body(), true);
        $this->assertSame($this->owner->email, $payload['requester_email']);
        $this->assertSame('invoice_collector', $payload['module']);
        $this->assertSame('ppa_non_contract', $payload['request_type']);
        $this->assertSame(16650000.0, (float) $payload['amount']);

        // 3. Check HMAC-SHA256 signature
        $timestamp = $capturedRequest->header('X-Timestamp')[0];
        $signature = $capturedRequest->header('X-Signature')[0];
        $expectedSignature = hash_hmac('sha256', $timestamp.'.'.$capturedRequest->body(), $secret);
        $this->assertSame($expectedSignature, $signature);

        // 4. Exactly 2 approval transactions created
        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::WAITING_APPROVAL, $this->transaction->status);
        $this->assertSame(TransactionStep::KADEP_REVIEW, $this->transaction->current_step);
        $this->assertCount(2, $this->transaction->approvalTransactions);

        $kadepApproval = $this->transaction->approvalTransactions->firstWhere('approvalFlow.step_no', 1);
        $kadivApproval = $this->transaction->approvalTransactions->firstWhere('approvalFlow.step_no', 2);

        $this->assertNotNull($kadepApproval);
        $this->assertNotNull($kadivApproval);
        $this->assertSame($this->kadep->id, $kadepApproval->approver_user_id);
        $this->assertSame($this->kadiv->id, $kadivApproval->approver_user_id);
        $this->assertSame(ApprovalStatus::PENDING, $kadepApproval->status);
        $this->assertSame(ApprovalStatus::PENDING, $kadivApproval->status);
    }

    public function test_documents_are_locked_while_approval_is_in_progress(): void
    {
        $this->test_uploading_all_required_documents_triggers_approval_with_owner_email_and_signed_portal_request();

        // Count is 2
        $this->assertDatabaseCount('approval_transactions', 2);

        // Re-upload a document while in WAITING_APPROVAL
        $invoiceDocType = DocumentType::where('code', 'PNK_INVOICE')->firstOrFail();

        $this->actingAs($this->owner)
            ->post(route('invoice-verification.transactions.documents.combined.store', $this->transaction), [
                'attachments' => [
                    [
                        'document_type_id' => $invoiceDocType->id,
                        'source_actor' => 'VENDOR',
                        'document_label' => $invoiceDocType->name,
                        'file' => UploadedFile::fake()->create('invoice_updated.pdf', 100, 'application/pdf'),
                        'document_information' => [
                            'document_number' => 'INV-UPDATED',
                            'document_date' => '2026-10-20',
                            'dpp_value' => 20000000,
                            'ppn_value' => 2200000,
                        ],
                    ],
                ],
            ])
            ->assertForbidden();

        // Must still have exactly 2 approval_transactions for this transaction
        $this->assertSame(2, ApprovalTransaction::where('transaction_id', $this->transaction->id)->count());
    }

    public function test_submit_with_missing_required_documents_keeps_sheet_and_transaction_in_draft(): void
    {
        $response = $this->actingAs($this->owner)
            ->from(route('invoice-verification.transactions.ppa-verification-sheets.edit', $this->transaction))
            ->post(route('invoice-verification.transactions.ppa-verification-sheets.submit', $this->transaction));

        $response->assertSessionHasErrors('approval');
        $this->assertSame(TransactionStatus::DRAFT, $this->transaction->fresh()->status);
        $this->assertSame('DRAFT', $this->transaction->fresh('ppaVerificationSheet')->ppaVerificationSheet->status->value);
        $this->assertCount(0, $this->transaction->approvalTransactions);
    }

    public function test_other_department_head_cannot_process_assigned_approval(): void
    {
        $this->test_uploading_all_required_documents_triggers_approval_with_owner_email_and_signed_portal_request();
        $otherKadep = User::factory()->create([
            'role_code' => RoleCode::KEPALA_DEPARTEMEN,
            'is_active' => true,
        ]);
        $approval = $this->transaction->approvalTransactions->firstWhere('approvalFlow.step_no', 1);

        $this->actingAs($otherKadep)
            ->put(route('invoice-verification.approvals.update', $approval), [
                'status' => 'APPROVED',
                'notes' => 'Tidak berhak.',
            ])
            ->assertForbidden();

        $this->assertSame(ApprovalStatus::PENDING, $approval->fresh()->status);
    }

    public function test_kadiv_cannot_process_before_kadep_completes(): void
    {
        $this->test_uploading_all_required_documents_triggers_approval_with_owner_email_and_signed_portal_request();

        $kadivApproval = $this->transaction->approvalTransactions->firstWhere('approvalFlow.step_no', 2);

        // Kadiv attempts to approve directly while Kadep is still PENDING
        $response = $this->actingAs($this->kadiv)
            ->from(route('invoice-verification.approvals.index'))
            ->put(route('invoice-verification.approvals.update', $kadivApproval), [
                'status' => 'APPROVED',
                'notes' => 'Langsung approve Kadiv tanpa Kadep',
            ]);

        $response->assertSessionHasErrors('approval');
        $this->assertSame(ApprovalStatus::PENDING, $kadivApproval->fresh()->status);
        $this->assertSame(TransactionStatus::WAITING_APPROVAL, $this->transaction->fresh()->status);
        $this->assertSame(TransactionStep::KADEP_REVIEW, $this->transaction->fresh()->current_step);
    }

    public function test_kadep_rejection_returns_transaction_to_pemohon_and_blocks_accounting(): void
    {
        $this->test_uploading_all_required_documents_triggers_approval_with_owner_email_and_signed_portal_request();

        $kadepApproval = $this->transaction->approvalTransactions->firstWhere('approvalFlow.step_no', 1);

        // Rejection requires notes
        $responseWithoutNotes = $this->actingAs($this->kadep)
            ->from(route('invoice-verification.approvals.index'))
            ->put(route('invoice-verification.approvals.update', $kadepApproval), [
                'status' => 'REJECTED',
                'notes' => '',
            ]);
        $responseWithoutNotes->assertSessionHasErrors('notes');

        // Kadep rejects with note
        $response = $this->actingAs($this->kadep)
            ->from(route('invoice-verification.approvals.index'))
            ->put(route('invoice-verification.approvals.update', $kadepApproval), [
                'status' => 'REJECTED',
                'notes' => 'Nominal tagihan tidak sesuai dengan memo realisasi.',
            ]);

        $response->assertRedirect(route('invoice-verification.approvals.index'));

        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::NOT_APPROVED, $this->transaction->status);
        $this->assertSame(TransactionStep::INTERNAL_DOCUMENT_UPLOAD, $this->transaction->current_step);
        $this->assertSame(ApprovalStatus::REJECTED, $kadepApproval->fresh()->status);
        $this->assertSame('Nominal tagihan tidak sesuai dengan memo realisasi.', $kadepApproval->fresh()->notes);

        // Transaction is NOT in Accounting review or submitted
        $this->assertNotSame(TransactionStatus::SUBMITTED, $this->transaction->status);
        $this->assertNotSame(TransactionStatus::IN_REVIEW, $this->transaction->status);
        $this->assertNotSame(TransactionStep::ACCOUNTING_VERIFICATION, $this->transaction->current_step);
    }

    public function test_kadiv_rejection_returns_transaction_to_pemohon_and_blocks_accounting(): void
    {
        $this->test_uploading_all_required_documents_triggers_approval_with_owner_email_and_signed_portal_request();

        $kadepApproval = $this->transaction->approvalTransactions->firstWhere('approvalFlow.step_no', 1);
        $kadivApproval = $this->transaction->approvalTransactions->firstWhere('approvalFlow.step_no', 2);

        // Step 1: Kadep approves
        $this->actingAs($this->kadep)
            ->put(route('invoice-verification.approvals.update', $kadepApproval), [
                'status' => 'APPROVED',
                'notes' => 'Dokumen sesuai.',
            ]);

        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::WAITING_APPROVAL, $this->transaction->status);
        $this->assertSame(TransactionStep::KADIV_REVIEW, $this->transaction->current_step);

        // Step 2: Kadiv rejects with note
        $this->actingAs($this->kadiv)
            ->put(route('invoice-verification.approvals.update', $kadivApproval), [
                'status' => 'REJECTED',
                'notes' => 'Pekerjaan belum diverifikasi di lapangan.',
            ]);

        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::NOT_APPROVED, $this->transaction->status);
        $this->assertSame(TransactionStep::INTERNAL_DOCUMENT_UPLOAD, $this->transaction->current_step);
        $this->assertSame(ApprovalStatus::REJECTED, $kadivApproval->fresh()->status);
        $this->assertNotSame(TransactionStatus::SUBMITTED, $this->transaction->status);
    }

    public function test_kadiv_approval_transitions_transaction_to_submitted_and_accounting_verification(): void
    {
        $this->test_uploading_all_required_documents_triggers_approval_with_owner_email_and_signed_portal_request();

        $kadepApproval = $this->transaction->approvalTransactions->firstWhere('approvalFlow.step_no', 1);
        $kadivApproval = $this->transaction->approvalTransactions->firstWhere('approvalFlow.step_no', 2);

        // Step 1: Kadep approves
        $this->actingAs($this->kadep)
            ->put(route('invoice-verification.approvals.update', $kadepApproval), [
                'status' => 'APPROVED',
                'notes' => 'Disetujui Kadep.',
            ]);

        $this->assertSame(TransactionStatus::WAITING_APPROVAL, $this->transaction->fresh()->status);
        $this->assertSame(TransactionStep::KADIV_REVIEW, $this->transaction->fresh()->current_step);

        // Step 2: Kadiv approves
        $this->actingAs($this->kadiv)
            ->put(route('invoice-verification.approvals.update', $kadivApproval), [
                'status' => 'APPROVED',
                'notes' => 'Disetujui Kadiv.',
            ]);

        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::SUBMITTED, $this->transaction->status);
        $this->assertSame(TransactionStep::ACCOUNTING_VERIFICATION, $this->transaction->current_step);
        $this->assertNotNull($this->transaction->submitted_at);
        $this->assertNotNull($this->transaction->numberingRegister);
        $this->assertStringStartsWith('PPA-', $this->transaction->numberingRegister->register_number);

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://portal.lrtjakarta.co.id/api/v1/signatures') {
                return false;
            }

            $payload = json_decode($request->body(), true);

            return data_get($payload, 'module') === 'invoice_collector'
                && $request->hasHeader('X-App-Code', 'invoice_collector')
                && ! $request->hasHeader('X-Satset-Signature');
        });

        // Accounting can now verify it
        $this->actingAs($this->accounting)
            ->get(route('invoice-verification.transactions.show', $this->transaction))
            ->assertOk()
            ->assertSee('Submitted')
            ->assertSee('Verifikasi akuntansi');
    }

    public function test_legacy_submit_endpoint_blocks_ppa_non_contract(): void
    {
        // PPA Non Contract cannot be directly submitted via old submit endpoint
        $response = $this->actingAs($this->owner)
            ->from(route('invoice-verification.transactions.show', $this->transaction))
            ->post(route('invoice-verification.transactions.submit', $this->transaction));

        $response->assertSessionHasErrors('transaction');
        $this->assertSame(TransactionStatus::DRAFT, $this->transaction->fresh()->status);
    }

    public function test_inbox_approval_page_and_transaction_detail_stepper(): void
    {
        $this->test_uploading_all_required_documents_triggers_approval_with_owner_email_and_signed_portal_request();

        // Kadep accesses Inbox Approval
        $response = $this->actingAs($this->kadep)
            ->get(route('invoice-verification.approvals.index'));

        $response->assertOk();
        $response->assertSee('Inbox Approval');
        $response->assertSee($this->transaction->registration_number);
        $response->assertSee('Kepala Departemen');

        // Kadiv accesses Inbox Approval
        $responseKadiv = $this->actingAs($this->kadiv)
            ->get(route('invoice-verification.approvals.index'));

        $responseKadiv->assertOk();
        $responseKadiv->assertSee('Kepala Divisi');

        // View detail transaction page shows Kadep & Kadiv Stepper
        $detailResponse = $this->actingAs($this->kadep)
            ->get(route('invoice-verification.transactions.show', $this->transaction));

        $detailResponse->assertOk();
        $detailResponse->assertSee('Persetujuan Kepala Departemen & Kepala Divisi', false);
        $detailResponse->assertSee('Kepala Departemen');
        $detailResponse->assertSee('Kepala Divisi');
        $detailResponse->assertSee('Setujui (Approve)');
        $detailResponse->assertSee('Tolak (Reject)');
    }

    public function test_portal_api_only_lists_the_current_step_for_the_assigned_email(): void
    {
        $this->test_uploading_all_required_documents_triggers_approval_with_owner_email_and_signed_portal_request();

        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.', 'test-secret-key-123');
        $response = $this->call('GET', '/api/portal/v1/invoice-collector/approvals', [
            'status' => 'pending', 'approver_email' => $this->kadep->email,
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json', 'HTTP_X_INVOICE_TIMESTAMP' => $timestamp,
            'HTTP_X_INVOICE_SIGNATURE' => $signature,
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.module', 'invoice_collector')
            ->assertJsonPath('data.data.0.approver.email', $this->kadep->email)
            ->assertJsonPath('data.data.0.step.number', 1);

        $kadivResponse = $this->call('GET', '/api/portal/v1/invoice-collector/approvals', [
            'status' => 'pending', 'approver_email' => $this->kadiv->email,
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json', 'HTTP_X_INVOICE_TIMESTAMP' => $timestamp,
            'HTTP_X_INVOICE_SIGNATURE' => $signature,
        ]);
        $kadivResponse->assertOk()->assertJsonCount(0, 'data.data');
    }

    public function test_portal_api_rejects_invalid_signature(): void
    {
        $this->withHeaders([
            'X-Invoice-Timestamp' => (string) now()->timestamp,
            'X-Invoice-Signature' => 'invalid',
        ])->getJson('/api/portal/v1/invoice-collector/approvals?status=pending&approver_email='.$this->kadep->email)
            ->assertUnauthorized();
    }

    public function test_accounting_completion_routes_ppa_non_contract_to_kadiv_finance_and_approval_transitions_to_received(): void
    {
        $this->test_kadiv_approval_transitions_transaction_to_submitted_and_accounting_verification();

        $this->assertSame(TransactionStatus::SUBMITTED, $this->transaction->fresh()->status);
        $this->assertSame(TransactionStep::ACCOUNTING_VERIFICATION, $this->transaction->fresh()->current_step);

        $verification = app(\App\Modules\InvoiceVerification\Services\AccountingVerificationService::class)
            ->getOrCreate($this->transaction->fresh(), $this->accounting);

        // Accounting marks verification VALID
        $res = $this->actingAs($this->accounting)
            ->put(route('invoice-verification.transactions.accounting-verifications.update', $this->transaction), [
                'administration_status' => 'VALID',
                'items' => $verification->items->map(fn ($item) => [
                    'transaction_document_id' => $item->transaction_document_id,
                    'status' => 'VALID',
                    'notes' => null,
                ])->all(),
                'notes' => 'Verifikasi akuntansi lengkap.',
            ]);

        $res->assertRedirect();

        // Transaction is routed to Kadiv Finance
        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::WAITING_APPROVAL, $this->transaction->status);
        $this->assertSame(TransactionStep::KADIV_FINANCE_REVIEW, $this->transaction->current_step);

        $step3Approval = $this->transaction->approvalTransactions->firstWhere('approvalFlow.step_no', 3);
        $this->assertNotNull($step3Approval);
        $this->assertSame(ApprovalStatus::PENDING, $step3Approval->status);
        $this->assertSame('adityawarman.nugroho@lrtjakarta.co.id', $step3Approval->approver?->email);

        // Kadiv Finance user approves Step 3
        $kadivFinanceUser = $step3Approval->approver;
        $this->assertNotNull($kadivFinanceUser);

        $this->actingAs($kadivFinanceUser)
            ->put(route('invoice-verification.approvals.update', $step3Approval), [
                'status' => 'APPROVED',
                'notes' => 'Disetujui Kepala Divisi Keuangan dan Akuntansi.',
            ])
            ->assertRedirect();

        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::COMPLETED, $this->transaction->status);
        $this->assertSame(TransactionStep::COMPLETED, $this->transaction->current_step);
        $this->assertNotNull($this->transaction->completed_at);
        $this->assertSame(ApprovalStatus::APPROVED, $step3Approval->fresh()->status);
        $this->assertNotNull($this->transaction->numberingRegister);

        $compiledDocument = $this->transaction->fresh('compiledDocument.items')->compiledDocument;
        $this->assertNotNull($compiledDocument);
        $this->assertNull($compiledDocument->compiled_file_path);
        $this->assertNull($compiledDocument->archive_path);
        $this->assertGreaterThan(0, $compiledDocument->total_files);
        $this->assertCount($compiledDocument->total_files, $compiledDocument->items);
    }

    public function test_sync_portal_approval_notifications_command_backfills_accounting_approved_transactions(): void
    {
        $this->test_kadiv_approval_transitions_transaction_to_submitted_and_accounting_verification();

        // Simulate a transaction that was marked verified by accounting before Kadiv Finance routing
        $verification = app(\App\Modules\InvoiceVerification\Services\AccountingVerificationService::class)
            ->getOrCreate($this->transaction->fresh(), $this->accounting);

        $verification->update([
            'status' => \App\Modules\InvoiceVerification\Domain\Enums\AccountingVerificationStatus::COMPLETED,
            'verified_at' => now(),
        ]);

        $this->transaction->update([
            'status' => TransactionStatus::RECEIVED,
            'current_step' => TransactionStep::ACCOUNTING_VERIFICATION,
        ]);

        $this->artisan('invoice-verification:sync-portal-approval-notifications')
            ->expectsOutputToContain('Transaksi REG-PNK-2026-TEST berhasil diarahkan ke Kadiv Finance')
            ->assertExitCode(0);

        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::WAITING_APPROVAL, $this->transaction->status);
        $this->assertSame(TransactionStep::KADIV_FINANCE_REVIEW, $this->transaction->current_step);

        $step3 = $this->transaction->approvalTransactions->firstWhere('approvalFlow.step_no', 3);
        $this->assertNotNull($step3);
        $this->assertSame(ApprovalStatus::PENDING, $step3->status);
    }

    public function test_kadiv_finance_approval_generates_qr_code_on_ppa_verification_sheet_pdf(): void
    {
        $this->test_accounting_completion_routes_ppa_non_contract_to_kadiv_finance_and_approval_transitions_to_received();

        $sheet = $this->transaction->ppaVerificationSheet;
        $this->assertNotNull($sheet);

        $generator = app(\App\Modules\InvoiceVerification\Services\Implementations\FunctionalPpaVerificationSheetPdfGenerator::class);
        $data = $generator->buildPpaNonContractData($sheet);

        // QR Base64 for Kadiv Finance should be generated
        $this->assertNotNull($data['accountingQrBase64']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $data['accountingQrBase64']);

        // Check preview response
        $response = $this->actingAs($this->owner)
            ->get(route('invoice-verification.transactions.ppa-verification-sheets.preview', $this->transaction));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        // Check detail page UI
        $detailResponse = $this->actingAs($this->owner)
            ->get(route('invoice-verification.transactions.show', $this->transaction));

        $detailResponse->assertOk();
        $detailResponse->assertSee('Persetujuan Kepala Departemen & Kepala Divisi serta Kadiv Keuangan', false);
        $detailResponse->assertSee('Verifikasi QR Portal');
        $detailResponse->assertDontSee('Edit Form & Checklist');
    }

    public function test_sync_portal_command_completes_step3_approved_transactions(): void
    {
        $this->test_accounting_completion_routes_ppa_non_contract_to_kadiv_finance_and_approval_transitions_to_received();

        // Simulate legacy state where Step 3 was approved before code deployment,
        // leaving transaction status at RECEIVED and current_step at FINANCE_PROCESS
        $this->transaction->update([
            'status' => TransactionStatus::RECEIVED,
            'current_step' => TransactionStep::FINANCE_PROCESS,
            'completed_at' => null,
        ]);

        $this->artisan('invoice-verification:sync-portal-approval-notifications')
            ->expectsOutputToContain('Transaksi REG-PNK-2026-TEST berhasil diselesaikan (Status: COMPLETED, Step: Selesai).')
            ->assertExitCode(0);

        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::COMPLETED, $this->transaction->status);
        $this->assertSame(TransactionStep::COMPLETED, $this->transaction->current_step);
        $this->assertNotNull($this->transaction->completed_at);
    }

    public function test_show_page_auto_heals_step3_approved_transaction_to_completed(): void
    {
        $this->test_accounting_completion_routes_ppa_non_contract_to_kadiv_finance_and_approval_transitions_to_received();

        // Simulate legacy state
        $this->transaction->update([
            'status' => TransactionStatus::RECEIVED,
            'current_step' => TransactionStep::FINANCE_PROCESS,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('invoice-verification.transactions.show', $this->transaction));

        $response->assertOk();
        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::COMPLETED, $this->transaction->status);
        $this->assertSame(TransactionStep::COMPLETED, $this->transaction->current_step);
    }

    public function test_sync_portal_command_with_mark_approved_option(): void
    {
        $this->test_kadiv_approval_transitions_transaction_to_submitted_and_accounting_verification();

        // Direct to Kadiv Finance (Step 3 PENDING)
        app(\App\Modules\InvoiceVerification\Services\ApprovalWorkflowService::class)->triggerKadivFinanceApproval($this->transaction, $this->accounting);
        $this->transaction->refresh();

        $this->assertSame(TransactionStatus::WAITING_APPROVAL, $this->transaction->status);
        $this->assertSame(TransactionStep::KADIV_FINANCE_REVIEW, $this->transaction->current_step);

        $this->artisan('invoice-verification:sync-portal-approval-notifications', [
            '--mark-approved' => $this->transaction->registration_number,
        ])
            ->expectsOutputToContain("Transaksi {$this->transaction->registration_number} berhasil disetujui & diselesaikan (COMPLETED)!")
            ->assertExitCode(0);

        $this->transaction->refresh();
        $this->assertSame(TransactionStatus::COMPLETED, $this->transaction->status);
        $this->assertSame(TransactionStep::COMPLETED, $this->transaction->current_step);

        $step3 = $this->transaction->approvalTransactions->firstWhere('approvalFlow.step_no', 3);
        $this->assertSame(ApprovalStatus::APPROVED, $step3->status);
    }
}
