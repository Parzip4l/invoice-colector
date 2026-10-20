<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use Database\Seeders\InvoiceVerification\InvoiceVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PpaNonContractDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InvoiceVerificationSeeder::class);
    }

    public function test_ppa_non_contract_form_and_upload_store_document_metadata(): void
    {
        Storage::fake(config('invoice_verification.storage.documents_disk', 'public'));

        $user = User::where('email', 'user.divisi@demo.local')->firstOrFail();
        $transactionType = TransactionType::where('code', 'PPA_NON_CONTRACT')->firstOrFail();
        $department = Department::where('division_id', $user->division_id)->firstOrFail();

        $this->actingAs($user)->post(route('invoice-verification.transactions.store'), [
            'transaction_type_id' => $transactionType->id,
            'department_id' => $department->id,
        ])->assertRedirect();

        $transaction = Transaction::where('owner_user_id', $user->id)
            ->where('transaction_type_id', $transactionType->id)
            ->latest('id')
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('invoice-verification.transactions.documents.show', $transaction))
            ->assertOk()
            ->assertSee('Nomor Invoice')
            ->assertSee('Tanggal Invoice')
            ->assertSee('Nominal DPP')
            ->assertSee('Nominal PPN')
            ->assertSee('Nomor Kwitansi')
            ->assertSee('Tanggal Kwitansi')
            ->assertSee('Nomor Faktur Pajak')
            ->assertSee('Tanggal Faktur Pajak')
            ->assertSee('Nomor Memo')
            ->assertSee('Laporan Pekerjaan');

        $documentTypes = DocumentType::where('transaction_type_id', $transactionType->id)
            ->where('source_type', 'VENDOR')
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->get();

        $attachments = $documentTypes->values()->map(function (DocumentType $documentType, int $index) {
            $information = match ($documentType->code) {
                'PNK_INVOICE' => [
                    'document_number' => 'INV-PNK-2026-001',
                    'document_date' => '2026-10-20',
                    'dpp_value' => 10000000,
                    'ppn_value' => 1100000,
                ],
                'PNK_KWITANSI' => [
                    'document_number' => 'KWT-PNK-2026-001',
                    'document_date' => '2026-10-20',
                ],
                'PNK_FAKTUR_PAJAK' => [
                    'document_number' => '010.000-26.00000001',
                    'document_date' => '2026-10-20',
                ],
                'PNK_MEMO_PERMOHONAN' => ['document_number' => 'MEMO-PNK-2026-001'],
                default => [],
            };

            return [
                'document_type_id' => $documentType->id,
                'source_actor' => 'VENDOR',
                'document_label' => $documentType->name,
                'document_information' => $information,
                'file' => UploadedFile::fake()->create("document-{$index}.pdf", 64, 'application/pdf'),
            ];
        })->all();

        $this->actingAs($user)
            ->post(route('invoice-verification.transactions.documents.combined.store', $transaction), [
                'attachments' => $attachments,
            ])
            ->assertRedirect(route('invoice-verification.transactions.ppa-verification-sheets.edit', $transaction));

        $invoiceDocument = $transaction->fresh()->latestDocuments()
            ->whereHas('documentType', fn ($query) => $query->where('code', 'PNK_INVOICE'))
            ->firstOrFail();
        $taxInvoiceDocument = $transaction->fresh()->latestDocuments()
            ->whereHas('documentType', fn ($query) => $query->where('code', 'PNK_FAKTUR_PAJAK'))
            ->firstOrFail();
        $receiptDocument = $transaction->fresh()->latestDocuments()
            ->whereHas('documentType', fn ($query) => $query->where('code', 'PNK_KWITANSI'))
            ->firstOrFail();
        $memoDocument = $transaction->fresh()->latestDocuments()
            ->whereHas('documentType', fn ($query) => $query->where('code', 'PNK_MEMO_PERMOHONAN'))
            ->firstOrFail();
        $workReportDocument = $transaction->fresh()->latestDocuments()
            ->whereHas('documentType', fn ($query) => $query->where('code', 'PNK_LAPORAN_PEKERJAAN'))
            ->firstOrFail();
        $metadata = $transaction->fresh('invoiceMetadata')->invoiceMetadata;

        $this->assertSame('INV-PNK-2026-001', $invoiceDocument->document_information_json['document_number']);
        $this->assertSame('2026-10-20', $invoiceDocument->document_information_json['document_date']);
        $this->assertSame(10000000, $invoiceDocument->document_information_json['dpp_value']);
        $this->assertSame(1100000, $invoiceDocument->document_information_json['ppn_value']);

        $this->assertSame('010.000-26.00000001', $taxInvoiceDocument->document_information_json['document_number']);
        $this->assertSame('2026-10-20', $taxInvoiceDocument->document_information_json['document_date']);

        $this->assertSame('KWT-PNK-2026-001', $receiptDocument->document_information_json['document_number']);
        $this->assertSame('2026-10-20', $receiptDocument->document_information_json['document_date']);

        $this->assertSame('MEMO-PNK-2026-001', $memoDocument->document_information_json['document_number']);
        $this->assertNotNull($workReportDocument);

        $this->assertSame('INV-PNK-2026-001', $metadata?->invoice_number);
        $this->assertSame('2026-10-20', $metadata?->invoice_date?->format('Y-m-d'));
        $this->assertSame('10000000.00', (string) $metadata?->invoice_value);
        $this->assertSame('1100000.00', (string) $metadata?->ppn_value);
        $this->assertSame('MEMO-PNK-2026-001', $metadata?->memo_number);

        $invoiceType = $documentTypes->firstWhere('code', 'PNK_INVOICE');

        $this->actingAs($user)
            ->post(route('invoice-verification.transactions.documents.combined.store', $transaction), [
                'attachments' => [[
                    'document_type_id' => $invoiceType->id,
                    'source_actor' => 'VENDOR',
                    'document_label' => $invoiceType->name,
                    'document_information' => [
                        'document_number' => 'INV-PNK-2026-001-REV',
                        'document_date' => '2026-10-21',
                        'dpp_value' => 12000000,
                        'ppn_value' => 1320000,
                    ],
                ]],
            ])
            ->assertRedirect(route('invoice-verification.transactions.ppa-verification-sheets.edit', $transaction));

        $updatedTransaction = $transaction->fresh('invoiceMetadata');
        $updatedInvoiceDocument = $updatedTransaction->latestDocuments()
            ->where('document_type_id', $invoiceType->id)
            ->firstOrFail();

        $this->assertSame('INV-PNK-2026-001-REV', $updatedInvoiceDocument->document_information_json['document_number']);
        $this->assertSame('2026-10-21', $updatedInvoiceDocument->document_information_json['document_date']);
        $this->assertSame('INV-PNK-2026-001-REV', $updatedTransaction->invoiceMetadata?->invoice_number);
        $this->assertSame('2026-10-21', $updatedTransaction->invoiceMetadata?->invoice_date?->format('Y-m-d'));
        $this->assertSame('12000000.00', (string) $updatedTransaction->invoiceMetadata?->invoice_value);
    }
}
