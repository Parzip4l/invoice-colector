<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\InvoiceMetadata;
use App\Modules\InvoiceVerification\Domain\Models\MemoRequest;
use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use App\Modules\InvoiceVerification\Services\Contracts\DocumentCompiler;
use App\Modules\InvoiceVerification\Services\FinalizationService;
use App\Modules\InvoiceVerification\Services\Implementations\PlaceholderDocumentCompiler;
use App\Modules\InvoiceVerification\Services\PpaVerificationSheetService;
use Database\Seeders\InvoiceVerification\InvoiceVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceVerificationModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InvoiceVerificationSeeder::class);
    }

    public function test_dashboard_is_accessible_for_seeded_user(): void
    {
        $user = User::where('email', 'admin.divisi@demo.local')->firstOrFail();

        $response = $this->actingAs($user)->get(route('invoice-verification.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard');
    }

    public function test_vendor_cannot_access_audit_logs(): void
    {
        $vendor = User::where('email', 'vendor@demo.local')->firstOrFail();

        $response = $this->actingAs($vendor)->get(route('invoice-verification.audit-logs.index'));

        $response->assertForbidden();
    }

    public function test_vendor_can_view_transaction_index(): void
    {
        $vendor = User::where('email', 'vendor@demo.local')->firstOrFail();
        $linkedVendor = $vendor->linkedVendor() ?? Vendor::firstOrFail();
        $agreementReference = AgreementReference::query()
            ->where('vendor_id', $linkedVendor->id)
            ->firstOrFail();

        Transaction::query()
            ->where('vendor_id', $linkedVendor->id)
            ->where('agreement_reference_id', $agreementReference->id)
            ->delete();

        $response = $this->actingAs($vendor)->get(route('invoice-verification.transactions.index'));

        $response->assertOk();
        $response->assertSee('Daftar Kontrak Vendor');
        $response->assertSee($agreementReference->contract_number);
        $response->assertSee('Upload Tagihan');
        $response->assertDontSee(route('invoice-verification.transactions.create'));
    }

    public function test_vendor_can_start_upload_from_contract(): void
    {
        $vendorUser = User::where('email', 'vendor@demo.local')->firstOrFail();
        $vendor = $vendorUser->linkedVendor() ?? Vendor::firstOrFail();
        $agreementReference = AgreementReference::query()
            ->where('vendor_id', $vendor->id)
            ->firstOrFail();

        Transaction::query()
            ->where('vendor_id', $vendor->id)
            ->where('agreement_reference_id', $agreementReference->id)
            ->delete();

        $response = $this->actingAs($vendorUser)
            ->post(route('invoice-verification.transactions.agreements.start', $agreementReference));

        $transaction = Transaction::query()
            ->where('vendor_id', $vendor->id)
            ->where('agreement_reference_id', $agreementReference->id)
            ->where('status', 'DRAFT')
            ->firstOrFail();

        $response->assertRedirect(route('invoice-verification.transactions.documents.show', $transaction));
        $this->assertNull($transaction->memo_request_id);
        $this->assertSame($vendorUser->id, $transaction->owner_user_id);
        $this->assertSame($agreementReference->contract_number, $transaction->contract_number);
    }

    public function test_vendor_can_open_start_upload_url_from_contract(): void
    {
        $vendorUser = User::where('email', 'vendor.logistik@demo.local')->firstOrFail();
        $vendor = $vendorUser->linkedVendor() ?? Vendor::firstOrFail();
        $agreementReference = AgreementReference::query()
            ->where('vendor_id', $vendor->id)
            ->firstOrFail();

        Transaction::query()
            ->where('vendor_id', $vendor->id)
            ->where('agreement_reference_id', $agreementReference->id)
            ->delete();

        $response = $this->actingAs($vendorUser)
            ->get(route('invoice-verification.transactions.agreements.start', $agreementReference));

        $transaction = Transaction::query()
            ->where('vendor_id', $vendor->id)
            ->where('agreement_reference_id', $agreementReference->id)
            ->where('status', 'DRAFT')
            ->firstOrFail();

        $response->assertRedirect(route('invoice-verification.transactions.documents.show', $transaction));
    }

    public function test_vendor_invoice_tax_invoice_and_receipt_dates_must_match(): void
    {
        Storage::fake(config('invoice_verification.storage.documents_disk', 'public'));

        $vendorUser = User::where('email', 'vendor@demo.local')->firstOrFail();
        $vendor = $vendorUser->linkedVendor() ?? Vendor::firstOrFail();
        $agreementReference = AgreementReference::query()
            ->where('vendor_id', $vendor->id)
            ->firstOrFail();

        Transaction::query()
            ->where('vendor_id', $vendor->id)
            ->where('agreement_reference_id', $agreementReference->id)
            ->delete();

        $this->actingAs($vendorUser)
            ->post(route('invoice-verification.transactions.agreements.start', $agreementReference))
            ->assertRedirect();

        $transaction = Transaction::query()
            ->where('vendor_id', $vendor->id)
            ->where('agreement_reference_id', $agreementReference->id)
            ->where('status', 'DRAFT')
            ->latest()
            ->firstOrFail();
        $documentTypes = DocumentType::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->whereIn('code', ['PPA_INVOICE', 'PPA_FAKTUR_PAJAK', 'PPA_KWITANSI'])
            ->get()
            ->keyBy('code');
        $invoice = $documentTypes->get('PPA_INVOICE');
        $taxInvoice = $documentTypes->get('PPA_FAKTUR_PAJAK');
        $receipt = $documentTypes->get('PPA_KWITANSI');

        $response = $this->actingAs($vendorUser)
            ->from(route('invoice-verification.transactions.documents.show', $transaction))
            ->post(route('invoice-verification.transactions.documents.ppa.store', $transaction), [
                'invoice_number' => 'INV-DATE-MISMATCH-001',
                'invoice_date' => '2026-07-22',
                'received_date' => '2026-07-22',
                'invoice_value' => 10000000,
                'documents' => [
                    $invoice->id => [
                        'document_type_id' => $invoice->id,
                        'document_information' => [
                            'document_number' => 'INV-DOC-001',
                            'document_date' => '2026-07-22',
                        ],
                        'file' => UploadedFile::fake()->create('invoice.pdf', 128, 'application/pdf'),
                    ],
                    $taxInvoice->id => [
                        'document_type_id' => $taxInvoice->id,
                        'document_information' => [
                            'document_number' => 'FP-001',
                            'document_date' => '2026-07-21',
                        ],
                        'file' => UploadedFile::fake()->create('faktur-pajak.pdf', 128, 'application/pdf'),
                    ],
                    $receipt->id => [
                        'document_type_id' => $receipt->id,
                        'document_information' => [
                            'document_number' => 'KW-001',
                            'document_date' => '2026-07-22',
                        ],
                        'file' => UploadedFile::fake()->create('kwitansi.pdf', 128, 'application/pdf'),
                    ],
                ],
            ]);

        $response->assertRedirect(route('invoice-verification.transactions.documents.show', $transaction));
        $response->assertSessionHasErrors([
            "documents.{$invoice->id}.document_information.document_date",
            "documents.{$taxInvoice->id}.document_information.document_date",
            "documents.{$receipt->id}.document_information.document_date",
        ]);
    }

    public function test_admin_creates_draft_and_vendor_uploads_invoice_documents(): void
    {
        Storage::fake(config('invoice_verification.storage.documents_disk', 'public'));

        $user = User::where('email', 'vendor@demo.local')->firstOrFail();
        $admin = User::where('email', 'admin.divisi@demo.local')->firstOrFail();
        $vendor = $user->linkedVendor();
        $agreementReference = AgreementReference::query()->where('vendor_id', $vendor?->id)->firstOrFail();
        $department = Department::query()->findOrFail($agreementReference->department_id);
        $memoRequest = MemoRequest::query()
            ->where('division_id', $agreementReference->division_id)
            ->where('department_id', $department->id)
            ->firstOrFail();
        $documentType = DocumentType::query()
            ->whereHas('transactionType', fn ($query) => $query->where('code', 'PPA'))
            ->where('code', 'PPA_INVOICE')
            ->firstOrFail();

        $response = $this->actingAs($admin)->post(route('invoice-verification.transactions.store'), [
            'transaction_type_id' => TransactionType::where('code', 'PPA')->firstOrFail()->id,
            'vendor_id' => $vendor->id,
            'division_id' => $agreementReference->division_id,
            'department_id' => $department->id,
            'memo_request_id' => $memoRequest->id,
            'agreement_reference_id' => $agreementReference->id,
            'description' => 'Transaksi pengujian fitur create transaction.',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::query()
            ->where('vendor_id', $vendor->id)
            ->where('status', 'DRAFT')
            ->latest()
            ->firstOrFail();

        $this->assertNull($transaction->invoiceMetadata);

        $uploadResponse = $this->actingAs($user)->post(route('invoice-verification.transactions.documents.ppa.store', $transaction), [
            'invoice_number' => 'INV-TEST-001',
            'invoice_date' => now()->toDateString(),
            'received_date' => now()->toDateString(),
            'bank_name' => 'Bank Central Asia',
            'invoice_value' => 10000000,
            'ppn_value' => 1100000,
            'documents' => [
                [
                    'document_type_id' => $documentType->id,
                    'document_information' => [
                        'document_number' => 'INV-DOC-001',
                        'document_date' => now()->toDateString(),
                    ],
                    'file' => UploadedFile::fake()->create('invoice.pdf', 128, 'application/pdf'),
                ],
            ],
        ]);

        $uploadResponse->assertRedirect();
        $transaction->refresh();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'title' => 'INV-TEST-001 - '.$agreementReference->contract_number,
            'status' => 'ADMIN_REVIEW',
        ]);
        $this->assertSame(0, $transaction->generatedDocuments()->count());
        $this->assertSame(0, $transaction->approvalTransactions()->count());
        $this->assertDatabaseHas('invoice_metadata', [
            'invoice_number' => 'INV-TEST-001',
            'contract_number' => $agreementReference->contract_number,
        ]);
        $this->assertDatabaseHas('transaction_documents', [
            'document_type_id' => $documentType->id,
            'source_actor' => 'VENDOR',
        ]);
        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    public function test_admin_divisi_can_upload_memo_request_file(): void
    {
        Storage::fake(config('invoice_verification.storage.documents_disk', 'public'));

        $user = User::where('email', 'admin.divisi@demo.local')->firstOrFail();

        $response = $this->actingAs($user)->post(route('invoice-verification.master-data.memo-requests.store'), [
            'memo_number' => 'MEMO-OPS-2026-099',
            'memo_date' => now()->toDateString(),
            'subject' => 'Memo Permohonan Pengadaan Darurat',
            'description' => 'Pengujian upload memo oleh admin.',
            'division_id' => $user->division_id,
            'department_id' => $user->department_id,
            'memo_file' => UploadedFile::fake()->create('memo-permohonan.pdf', 128, 'application/pdf'),
        ]);

        $response->assertRedirect();

        $memoRequest = MemoRequest::query()->where('memo_number', 'MEMO-OPS-2026-099')->firstOrFail();

        $this->assertNotNull($memoRequest->file_path);
        Storage::disk($memoRequest->file_disk)->assertExists($memoRequest->file_path);
    }

    public function test_admin_review_sends_transaction_directly_to_admin_document_generation(): void
    {
        Storage::fake(config('invoice_verification.storage.documents_disk', 'public'));

        $vendor = User::where('email', 'vendor@demo.local')->firstOrFail();
        $admin = User::where('email', 'admin.divisi@demo.local')->firstOrFail();
        $transaction = $this->createTransactionOfType('PPA', 'INV-ADMIN-REVIEW-001', $vendor);
        $document = $transaction->fresh('latestDocuments')->latestDocuments()->firstOrFail();

        $response = $this->actingAs($admin)->put(route('invoice-verification.vendor-reviews.update', $document), [
            'status' => 'ACCEPTED',
        ]);

        $response->assertRedirect(route('invoice-verification.vendor-reviews.index'));

        $transaction->refresh();
        $this->assertSame('ADMIN_GENERATE_DOCUMENTS', $transaction->status->value);
        $this->assertSame('INITIAL_DOCUMENT_GENERATION', $transaction->current_step->value);
        $this->assertSame(0, $transaction->generatedDocuments()->count());
        $this->assertSame(0, $transaction->approvalTransactions()->count());

        $this->actingAs($admin)->post(route('invoice-verification.transactions.admin-documents.generate', $transaction))
            ->assertRedirect(route('invoice-verification.transactions.show', $transaction));

        $transaction->refresh();
        $this->assertSame('ACCOUNTING_VERIFICATION', $transaction->status->value);
        $this->assertSame(1, $transaction->generatedDocuments()->count());
        $this->assertDatabaseHas('ppa_verification_sheets', [
            'transaction_id' => $transaction->id,
            'status' => 'SUBMITTED',
        ]);
    }

    public function test_admin_divisi_can_preview_uploaded_vendor_document(): void
    {
        Storage::fake(config('invoice_verification.storage.documents_disk', 'public'));

        $vendor = User::where('email', 'vendor@demo.local')->firstOrFail();
        $admin = User::where('email', 'admin.divisi@demo.local')->firstOrFail();
        $transaction = $this->createTransactionOfType('PPA', 'INV-PREVIEW-001', $vendor);
        $document = $transaction->fresh('latestDocuments')->latestDocuments()->firstOrFail();

        $response = $this->actingAs($admin)->get(route('invoice-verification.transaction-documents.preview', $document));

        $response->assertOk();
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
    }

    public function test_accounting_can_preview_generated_documents_and_checklist(): void
    {
        Storage::fake(config('invoice_verification.storage.documents_disk', 'public'));

        $vendor = User::where('email', 'vendor@demo.local')->firstOrFail();
        $admin = User::where('email', 'admin.divisi@demo.local')->firstOrFail();
        $accounting = User::where('email', 'akuntansi@demo.local')->firstOrFail();
        $transaction = $this->createTransactionOfType('PPA', 'INV-ACCOUNTING-PREVIEW-001', $vendor);
        $document = $transaction->fresh('latestDocuments')->latestDocuments()->firstOrFail();

        $this->actingAs($admin)->put(route('invoice-verification.vendor-reviews.update', $document), [
            'status' => 'ACCEPTED',
        ]);

        $this->generateAdminDocuments($transaction->fresh(), $admin);

        $generatedDocument = $transaction->fresh('generatedDocuments')->generatedDocuments()->firstOrFail();

        $generatedPreviewResponse = $this->actingAs($accounting)->get(route('invoice-verification.generated-documents.preview', $generatedDocument));
        $generatedPreviewResponse->assertOk();
        $this->assertStringContainsString('inline', $generatedPreviewResponse->headers->get('content-disposition'));

        $checklistPreviewResponse = $this->actingAs($accounting)->get(route('invoice-verification.transactions.ppa-verification-sheets.preview', $transaction));
        $checklistPreviewResponse->assertOk();
        $this->assertStringContainsString('inline', $checklistPreviewResponse->headers->get('content-disposition'));
    }

    public function test_accounting_verification_page_shows_preview_links_and_button_toggles(): void
    {
        $accounting = User::where('email', 'akuntansi@demo.local')->firstOrFail();
        $transaction = Transaction::query()
            ->where('registration_number', 'TRX/DEMO/2026/0004')
            ->firstOrFail();

        $response = $this->actingAs($accounting)->get(
            route('invoice-verification.transactions.accounting-verifications.edit', $transaction)
        );

        $response->assertOk();
        $response->assertSee('Preview File');
        $response->assertSee('Approve');
        $response->assertSee('Reject');
        $response->assertDontSee('name="administration_status" class="form-select"', false);
    }

    public function test_rejected_document_returns_to_vendor_revision_list_and_can_be_reuploaded(): void
    {
        Storage::fake(config('invoice_verification.storage.documents_disk', 'public'));

        $vendor = User::where('email', 'vendor@demo.local')->firstOrFail();
        $admin = User::where('email', 'admin.divisi@demo.local')->firstOrFail();
        $transaction = $this->createTransactionOfType('PPA', 'INV-REVISION-001', $vendor);
        $document = $transaction->fresh('latestDocuments')->latestDocuments()->firstOrFail();

        $rejectResponse = $this->actingAs($admin)->put(route('invoice-verification.vendor-reviews.update', $document), [
            'status' => 'REVISION_REQUIRED',
            'notes' => 'Nomor invoice belum sesuai.',
        ]);

        $rejectResponse->assertRedirect(route('invoice-verification.vendor-reviews.index'));

        $transaction->refresh();
        $this->assertSame('REVISION_IN_PROGRESS', $transaction->status->value);
        $this->assertSame('REVISION_REQUIRED', $document->fresh()->status->value);

        $revisionListResponse = $this->actingAs($vendor)->get(route('invoice-verification.transactions.index', [
            'status' => 'REVISION_IN_PROGRESS',
        ]));

        $revisionListResponse->assertOk();
        $revisionListResponse->assertSee($transaction->registration_number);
        $revisionListResponse->assertSee('Upload Ulang');

        $uploadPageResponse = $this->actingAs($vendor)->get(route('invoice-verification.transactions.documents.show', $transaction));

        $uploadPageResponse->assertOk();
        $uploadPageResponse->assertSee('Nomor invoice belum sesuai.');

        $detailPageResponse = $this->actingAs($vendor)->get(route('invoice-verification.transactions.show', $transaction));

        $detailPageResponse->assertOk();
        $detailPageResponse->assertSee('Dokumen Transaksi');
        $detailPageResponse->assertDontSee('Generated Initial Documents');
        $detailPageResponse->assertDontSee('Mismatch Checklist PPA');
        $detailPageResponse->assertDontSee('Audit Trail');
        $detailPageResponse->assertDontSee('Timeline');

        $reuploadResponse = $this->actingAs($vendor)->post(route('invoice-verification.transactions.documents.ppa.store', $transaction), [
            'invoice_number' => $transaction->invoiceMetadata?->invoice_number,
            'invoice_date' => $transaction->invoiceMetadata?->invoice_date?->format('Y-m-d') ?? now()->toDateString(),
            'received_date' => $transaction->invoiceMetadata?->received_date?->format('Y-m-d') ?? now()->toDateString(),
            'bank_name' => $transaction->invoiceMetadata?->bank_name,
            'invoice_value' => $transaction->invoiceMetadata?->invoice_value ?? 10000000,
            'ppn_value' => $transaction->invoiceMetadata?->ppn_value,
            'documents' => [
                [
                    'document_type_id' => $document->document_type_id,
                    'document_information' => [
                        'document_number' => 'REV-INV-001',
                        'document_date' => now()->toDateString(),
                        'notes' => 'Upload ulang setelah revisi Admin Divisi.',
                    ],
                    'file' => UploadedFile::fake()->create('invoice-revisi.pdf', 128, 'application/pdf'),
                ],
            ],
        ]);

        $reuploadResponse->assertRedirect(route('invoice-verification.transactions.documents.show', $transaction));

        $transaction->refresh();
        $this->assertSame('ADMIN_REVIEW', $transaction->status->value);
        $this->assertFalse($document->fresh()->is_latest);

        $latestDocument = TransactionDocument::query()
            ->where('transaction_id', $transaction->id)
            ->where('document_type_id', $document->document_type_id)
            ->where('is_latest', true)
            ->firstOrFail();

        $this->assertSame('UNDER_REVIEW', $latestDocument->status->value);
        $this->assertSame('REV-INV-001', $latestDocument->document_information_json['document_number']);
    }

    public function test_accounting_reject_note_is_visible_to_vendor_for_revision(): void
    {
        $accounting = User::where('email', 'akuntansi@demo.local')->firstOrFail();
        $vendor = User::where('email', 'vendor.konstruksi@demo.local')->firstOrFail();
        $transaction = Transaction::query()
            ->where('registration_number', 'TRX/DEMO/2026/0004')
            ->firstOrFail();
        $verification = $transaction->accountingVerification()->with('items.transactionDocument')->firstOrFail();
        $rejectedItem = $verification->items->firstOrFail();
        $revisionNote = 'Nominal pada invoice tidak sesuai dengan kwitansi.';

        $items = $verification->items->values()->map(fn ($item, $index) => [
            'transaction_document_id' => $item->transaction_document_id,
            'status' => $index === 0 ? 'REVISION_REQUIRED' : 'VALID',
            'notes' => $index === 0 ? $revisionNote : null,
        ])->all();

        $response = $this->actingAs($accounting)->put(
            route('invoice-verification.transactions.accounting-verifications.update', $transaction),
            [
                'administration_status' => 'VALID',
                'items' => $items,
                'notes' => 'Mohon revisi dokumen invoicing.',
            ],
        );

        $response->assertRedirect(route('invoice-verification.transactions.show', $transaction));

        $transaction->refresh();
        $this->assertSame('REVISION_IN_PROGRESS', $transaction->status->value);
        $this->assertSame('REVISION_REQUIRED', $rejectedItem->transactionDocument->fresh()->status->value);

        $uploadPageResponse = $this->actingAs($vendor)->get(route('invoice-verification.transactions.documents.show', $transaction));

        $uploadPageResponse->assertOk();
        $uploadPageResponse->assertSee($revisionNote);
    }

    public function test_vendor_can_upload_ppa_transaction_document_with_document_information(): void
    {
        Storage::fake(config('invoice_verification.storage.documents_disk', 'public'));

        $user = User::where('email', 'vendor@demo.local')->firstOrFail();
        $transaction = $this->createTransactionOfType('PPA', 'INV-UPLOAD-001', $user);
        $documentType = DocumentType::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('code', 'PPA_BAPP')
            ->firstOrFail();
        $transaction->update([
            'status' => 'REVISION_IN_PROGRESS',
            'current_step' => 'VENDOR_INVOICE_INPUT',
        ]);

        $response = $this->actingAs($user)->post(
            route('invoice-verification.transactions.documents.ppa.store', $transaction),
            [
                'invoice_number' => $transaction->invoiceMetadata?->invoice_number,
                'invoice_date' => $transaction->invoiceMetadata?->invoice_date?->format('Y-m-d') ?? now()->toDateString(),
                'received_date' => $transaction->invoiceMetadata?->received_date?->format('Y-m-d') ?? now()->toDateString(),
                'bank_name' => $transaction->invoiceMetadata?->bank_name,
                'invoice_value' => $transaction->invoiceMetadata?->invoice_value ?? 10000000,
                'ppn_value' => $transaction->invoiceMetadata?->ppn_value,
                'documents' => [
                    [
                        'document_type_id' => $documentType->id,
                        'document_information' => [
                            'document_number' => 'BAPP-001/IV/2026',
                            'document_date' => now()->toDateString(),
                            'notes' => 'Dokumen hasil upload vendor untuk pengujian.',
                        ],
                        'file' => UploadedFile::fake()->create('bapp.pdf', 128, 'application/pdf'),
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('invoice-verification.transactions.documents.show', $transaction));

        $document = TransactionDocument::query()
            ->where('transaction_id', $transaction->id)
            ->where('document_type_id', $documentType->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('BAPP-001/IV/2026', $document->document_information_json['document_number']);
        $this->assertSame(now()->toDateString(), $document->document_information_json['document_date']);
        $this->assertSame('Dokumen hasil upload vendor untuk pengujian.', $document->document_information_json['notes']);
        Storage::disk($document->file_disk)->assertExists($document->file_path);
    }

    public function test_admin_divisi_cannot_upload_ppa_transaction_document(): void
    {
        Storage::fake(config('invoice_verification.storage.documents_disk', 'public'));

        $user = User::where('email', 'admin.divisi@demo.local')->firstOrFail();
        $vendor = User::where('email', 'vendor@demo.local')->firstOrFail();
        $transaction = $this->createTransactionOfType('PPA', 'INV-UPLOAD-002', $vendor);
        $documentType = DocumentType::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('code', 'PPA_BAPP')
            ->firstOrFail();

        $response = $this->actingAs($user)->post(
            route('invoice-verification.transactions.documents.ppa.store', $transaction),
            [
                'documents' => [
                    [
                        'document_type_id' => $documentType->id,
                        'document_information' => [
                            'document_number' => 'BAPP-002/IV/2026',
                            'document_date' => now()->toDateString(),
                        ],
                        'file' => UploadedFile::fake()->create('bapp-admin.pdf', 128, 'application/pdf'),
                    ],
                ],
            ]
        );

        $response->assertForbidden();
    }

    public function test_admin_divisi_can_upload_agreement_reference_file(): void
    {
        Storage::fake(config('invoice_verification.storage.documents_disk', 'public'));

        $user = User::where('email', 'admin.divisi@demo.local')->firstOrFail();

        $response = $this->actingAs($user)->post(route('invoice-verification.master-data.agreement-references.store'), [
            'vendor_id' => Vendor::firstOrFail()->id,
            'contract_number' => 'SPK-LRTJ-2026-099',
            'contract_value' => 45000000,
            'effective_date' => now()->toDateString(),
            'expired_at' => now()->addMonth()->toDateString(),
            'agreement_file' => UploadedFile::fake()->create('kontrak.pdf', 128, 'application/pdf'),
        ]);

        $response->assertRedirect();

        $agreement = AgreementReference::query()->where('contract_number', 'SPK-LRTJ-2026-099')->firstOrFail();

        $this->assertNotNull($agreement->file_path);
        Storage::disk($agreement->file_disk)->assertExists($agreement->file_path);
    }

    public function test_akuntansi_cannot_upload_memo_request_file(): void
    {
        Storage::fake(config('invoice_verification.storage.documents_disk', 'public'));

        $user = User::where('email', 'akuntansi@demo.local')->firstOrFail();

        $response = $this->actingAs($user)->post(route('invoice-verification.master-data.memo-requests.store'), [
            'memo_number' => 'MEMO-OPS-2026-100',
            'memo_date' => now()->toDateString(),
            'subject' => 'Memo Tidak Boleh',
            'division_id' => $user->division_id,
            'department_id' => $user->department_id,
            'memo_file' => UploadedFile::fake()->create('memo.pdf', 64, 'application/pdf'),
        ]);

        $response->assertForbidden();
    }

    public function test_ppa_verification_sheet_generates_pdf_when_submitted(): void
    {
        $user = User::where('email', 'admin.divisi@demo.local')->firstOrFail();
        $vendor = User::where('email', 'vendor@demo.local')->firstOrFail();
        $transaction = $this->createTransactionOfType('PPA', 'INV-PPA-001', $vendor);

        /** @var PpaVerificationSheetService $service */
        $service = app(PpaVerificationSheetService::class);
        $sheet = $service->getOrCreate($transaction, $user);

        $service->saveChecklist($transaction, $user, $sheet->items->map(fn ($item) => [
            'document_type_id' => $item->document_type_id,
            'attachment_status' => in_array($item->documentType->code, ['PPA_MEMO_PERMOHONAN', 'PPA_PERJANJIAN'], true) ? 'ATTACHED' : 'NOT_ATTACHED',
            'notes' => null,
        ])->all());

        $submitted = $service->submit($transaction->fresh(), $user);

        $this->assertInstanceOf(PpaVerificationSheet::class, $submitted);
        $this->assertNotNull($submitted->file_path);
        $this->assertTrue(Storage::disk($submitted->file_disk)->exists($submitted->file_path));
    }

    public function test_finalization_creates_compiled_pdf_and_archive(): void
    {
        $this->app->bind(DocumentCompiler::class, PlaceholderDocumentCompiler::class);

        $user = User::where('email', 'admin.divisi@demo.local')->firstOrFail();
        $vendor = User::where('email', 'vendor@demo.local')->firstOrFail();
        $transaction = $this->createTransactionOfType('SPU', 'INV-SPU-001', $vendor);

        app(FinalizationService::class)->finalize($transaction->fresh([
            'transactionType',
            'invoiceMetadata',
            'vendor',
            'generatedDocuments',
            'latestDocuments.documentType',
        ]), $user);

        $compiled = $transaction->fresh()->compiledDocument;

        $this->assertNotNull($compiled);
        $this->assertTrue(Storage::disk($compiled->compiled_file_disk)->exists($compiled->compiled_file_path));
        $this->assertTrue(Storage::disk($compiled->archive_disk)->exists($compiled->archive_path));
    }

    protected function createTransactionOfType(string $transactionTypeCode, string $invoiceNumber, User $user): Transaction
    {
        $transactionType = TransactionType::where('code', $transactionTypeCode)->firstOrFail();
        $admin = User::where('email', 'admin.divisi@demo.local')->firstOrFail();
        $vendor = $user->linkedVendor() ?? Vendor::firstOrFail();
        $agreementReference = AgreementReference::query()
            ->where('vendor_id', $vendor->id)
            ->firstOrFail();
        $department = Department::query()->findOrFail($agreementReference->department_id);
        $memoRequest = MemoRequest::query()
            ->where('division_id', $agreementReference->division_id)
            ->where('department_id', $department->id)
            ->firstOrFail();
        $response = $this->actingAs($admin)->post(route('invoice-verification.transactions.store'), [
            'transaction_type_id' => $transactionType->id,
            'vendor_id' => $vendor->id,
            'division_id' => $agreementReference->division_id,
            'department_id' => $department->id,
            'memo_request_id' => $memoRequest->id,
            'agreement_reference_id' => $agreementReference->id,
            'description' => 'Testing '.$transactionTypeCode,
        ]);

        $response->assertRedirect();

        $transaction = Transaction::query()
            ->where('vendor_id', $vendor->id)
            ->where('description', 'Testing '.$transactionTypeCode)
            ->latest()
            ->firstOrFail();

        if ($transactionTypeCode !== 'PPA') {
            InvoiceMetadata::updateOrCreate(
                ['transaction_id' => $transaction->id],
                [
                    'vendor_id' => $vendor->id,
                    'invoice_number' => $invoiceNumber,
                    'invoice_date' => now()->toDateString(),
                    'received_date' => now()->toDateString(),
                    'bank_name' => 'Bank Central Asia',
                    'memo_number' => $memoRequest->memo_number,
                    'contract_number' => $agreementReference->contract_number,
                    'contract_value' => $agreementReference->contract_value,
                    'invoice_value' => 10000000,
                    'ppn_value' => 1100000,
                    'description' => 'Testing '.$transactionTypeCode,
                ],
            );

            return $transaction->fresh('invoiceMetadata');
        }

        $documentType = DocumentType::query()
            ->where('transaction_type_id', $transactionType->id)
            ->where('source_type', 'VENDOR')
            ->firstOrFail();

        $uploadResponse = $this->actingAs($user)->post(route('invoice-verification.transactions.documents.ppa.store', $transaction), [
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now()->toDateString(),
            'received_date' => now()->toDateString(),
            'bank_name' => 'Bank Central Asia',
            'invoice_value' => 10000000,
            'ppn_value' => 1100000,
            'documents' => [
                [
                    'document_type_id' => $documentType->id,
                    'document_information' => [
                        'document_number' => 'DOC-'.$invoiceNumber,
                        'document_date' => now()->toDateString(),
                    ],
                    'file' => UploadedFile::fake()->create(strtolower($invoiceNumber).'.pdf', 128, 'application/pdf'),
                ],
            ],
        ]);

        $uploadResponse->assertRedirect();

        return Transaction::query()
            ->whereHas('invoiceMetadata', fn ($query) => $query->where('invoice_number', $invoiceNumber))
            ->latest()
            ->firstOrFail();
    }

    protected function generateAdminDocuments(Transaction $transaction, User $admin): void
    {
        $this->actingAs($admin)->post(route('invoice-verification.transactions.admin-documents.generate', $transaction->fresh()))
            ->assertRedirect(route('invoice-verification.transactions.show', $transaction));
    }
}
