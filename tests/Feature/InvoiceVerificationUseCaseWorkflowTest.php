<?php

namespace Tests\Feature;

use App\Mail\InvoiceTransactionReceivedMail;
use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\MemoRequest;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use Database\Seeders\InvoiceVerification\InvoiceVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceVerificationUseCaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InvoiceVerificationSeeder::class);
    }

    public function test_transaction_numbers_are_generated_per_type(): void
    {
        $externalVendor = User::where('email', 'vendor@demo.local')->firstOrFail();
        $internalVendor = User::where('email', 'user.divisi@demo.local')->firstOrFail();
        $internalVendor->division()->update(['petty_cash_ceiling' => 1000000]);
        $memo = $this->memoFor($internalVendor);

        $ppa = $this->createPpaContract($externalVendor);
        $pnk = $this->createTransaction($internalVendor, 'PPA_NON_CONTRACT', $memo, [
            'activity_name' => 'Kegiatan Non Kontrak',
            'transaction_bank_name' => 'BCA',
            'transaction_account_number' => '1234567890',
        ]);
        $spu = $this->createTransaction($internalVendor, 'SPU', $memo, [
            'activity_name' => 'Uang muka pekerjaan',
            'transaction_bank_name' => 'BCA',
            'transaction_account_number' => '1234567890',
            'spu_amount' => 500000,
        ]);
        $spuk = $this->createTransaction($internalVendor, 'SPUK', $memo, [
            'parent_spu_transaction_id' => $spu->id,
            'accountability_amount' => 125000,
            'remaining_amount' => 999999,
        ]);
        $kk = $this->createTransaction($internalVendor, 'KAS_KECIL', $memo, [
            'period' => 'Juli 2026',
            'petty_cash_remaining_amount' => 250000,
            'petty_cash_top_up_amount' => 1,
        ]);

        $this->assertSame('PPA-00009', $ppa->registration_number);
        $this->assertSame('PNK-00007', $pnk->registration_number);
        $this->assertSame('SPU-00009', $spu->registration_number);
        $this->assertSame('SPUK-00005', $spuk->registration_number);
        $this->assertSame('KK-00005', $kk->registration_number);
        $this->assertSame('375000.00', $spuk->remaining_amount);
        $this->assertSame('750000.00', $kk->petty_cash_top_up_amount);
    }

    public function test_demo_workflow_seeder_creates_rich_status_distribution(): void
    {
        $demoTransactions = Transaction::query()
            ->where(function ($query) {
                $query->where('registration_number', 'like', 'PPA-%')
                    ->orWhere('registration_number', 'like', 'PNK-%')
                    ->orWhere('registration_number', 'like', 'SPU-%')
                    ->orWhere('registration_number', 'like', 'SPUK-%')
                    ->orWhere('registration_number', 'like', 'KK-%');
            })
            ->get();

        $this->assertCount(30, $demoTransactions);

        foreach (['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'NOT_APPROVED', 'RECEIVED', 'SCHEDULING_PAYMENT', 'PAID'] as $status) {
            $this->assertTrue(
                $demoTransactions->contains(fn (Transaction $transaction) => $transaction->status->value === $status),
                'Seeder missing demo transaction with status '.$status,
            );
        }
    }

    public function test_vendor_type_authorization_is_enforced(): void
    {
        $externalVendor = User::where('email', 'vendor@demo.local')->firstOrFail();
        $internalVendor = User::where('email', 'user.divisi@demo.local')->firstOrFail();
        $memo = $this->memoFor($internalVendor);

        $this->actingAs($externalVendor)->post(route('invoice-verification.transactions.store'), [
            'transaction_type_id' => TransactionType::where('code', 'SPU')->firstOrFail()->id,
            'division_id' => $externalVendor->division_id,
            'department_id' => $externalVendor->department_id,
            'memo_request_id' => $memo->id,
            'activity_name' => 'Tidak boleh',
            'transaction_bank_name' => 'BCA',
            'transaction_account_number' => '1234567890',
            'spu_amount' => 1000,
        ])->assertSessionHasErrors('transaction_type_id');

        $this->actingAs($internalVendor)->post(route('invoice-verification.transactions.store'), [
            'transaction_type_id' => TransactionType::where('code', 'PPA')->firstOrFail()->id,
            'division_id' => $internalVendor->division_id,
            'department_id' => $internalVendor->department_id,
            'memo_request_id' => $memo->id,
        ])->assertSessionHasErrors('transaction_type_id');
    }

    public function test_accounting_received_sends_email_and_finance_can_mark_paid(): void
    {
        Storage::fake(config('invoice_verification.storage.documents_disk', 'public'));
        Mail::fake();

        $internalVendor = User::where('email', 'user.divisi@demo.local')->firstOrFail();
        $accounting = User::where('email', 'akuntansi@demo.local')->firstOrFail();
        $finance = User::where('email', 'finance@demo.local')->firstOrFail();
        $memo = $this->memoFor($internalVendor);
        $transaction = $this->createTransaction($internalVendor, 'SPU', $memo, [
            'activity_name' => 'Uang muka pekerjaan',
            'transaction_bank_name' => 'BCA',
            'transaction_account_number' => '1234567890',
            'spu_amount' => 500000,
        ]);

        $documentType = DocumentType::where('transaction_type_id', $transaction->transaction_type_id)
            ->where('source_type', 'VENDOR')
            ->firstOrFail();

        $this->actingAs($internalVendor)->post(route('invoice-verification.transactions.documents.combined.store', $transaction), [
            'attachments' => [
                [
                    'document_type_id' => $documentType->id,
                    'source_actor' => 'VENDOR',
                    'document_label' => $documentType->name,
                    'file' => UploadedFile::fake()->create('support.pdf', 10, 'application/pdf'),
                ],
            ],
        ])->assertRedirect();

        $this->actingAs($internalVendor)->post(route('invoice-verification.transactions.submit', $transaction))->assertRedirect();
        $this->assertSame('SUBMITTED', $transaction->fresh()->status->value);

        $this->actingAs($accounting)->get(route('invoice-verification.transactions.accounting-verifications.edit', $transaction))->assertOk();
        $verification = $transaction->fresh()->accountingVerification()->with('items')->firstOrFail();

        $this->actingAs($accounting)->put(route('invoice-verification.transactions.accounting-verifications.update', $transaction), [
            'administration_status' => 'VALID',
            'items' => $verification->items->map(fn ($item) => [
                'transaction_document_id' => $item->transaction_document_id,
                'status' => 'VALID',
                'notes' => null,
            ])->all(),
            'notes' => null,
        ])->assertRedirect();

        $this->assertSame('RECEIVED', $transaction->fresh()->status->value);
        Mail::assertSent(InvoiceTransactionReceivedMail::class);

        $this->actingAs($finance)->get(route('invoice-verification.finance.index'))->assertOk()->assertSee($transaction->registration_number);
        $this->actingAs($finance)->post(route('invoice-verification.finance.schedule', $transaction), [
            'scheduled_payment_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertRedirect();
        $this->assertSame('SCHEDULING_PAYMENT', $transaction->fresh()->status->value);

        $this->actingAs($finance)->post(route('invoice-verification.finance.paid', $transaction))
            ->assertSessionHasErrors('paid_at');

        $paidAt = now()->toDateString();

        $this->actingAs($finance)->post(route('invoice-verification.finance.paid', $transaction), [
            'paid_at' => $paidAt,
        ])
            ->assertSessionHasErrors('invoice_metadata');

        $transaction->invoiceMetadata()->updateOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'vendor_id' => $transaction->vendor_id,
                'invoice_number' => 'INV-SPU-PAID-001',
                'invoice_date' => now()->toDateString(),
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'User Divisi Demo',
                'invoice_value' => 500000,
            ],
        );

        $this->actingAs($finance)->post(route('invoice-verification.finance.paid', $transaction), [
            'paid_at' => $paidAt,
        ])
            ->assertSessionHasErrors('payment_proof');

        $this->actingAs($finance)->post(route('invoice-verification.finance.payment-proof', $transaction), [
            'payment_proof' => UploadedFile::fake()->create('transfer.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($finance)->post(route('invoice-verification.finance.paid', $transaction), [
            'paid_at' => $paidAt,
        ])->assertRedirect();
        $this->assertSame('PAID', $transaction->fresh()->status->value);
        $this->assertSame($paidAt, $transaction->fresh()->paid_at?->format('Y-m-d'));
    }

    private function createPpaContract(User $vendorUser): Transaction
    {
        $vendor = $vendorUser->linkedVendor() ?? Vendor::firstOrFail();
        $agreement = $vendor->agreementReferences()->firstOrFail();
        $memo = MemoRequest::where('division_id', $agreement->division_id)
            ->where('department_id', $agreement->department_id)
            ->firstOrFail();

        return $this->createTransaction($vendorUser, 'PPA', $memo, [
            'department_id' => $agreement->department_id,
            'agreement_reference_id' => $agreement->id,
            'vendor_id' => $vendor->id,
        ]);
    }

    private function createTransaction(User $user, string $typeCode, MemoRequest $memo, array $payload = []): Transaction
    {
        $response = $this->actingAs($user)->post(route('invoice-verification.transactions.store'), [
            'transaction_type_id' => TransactionType::where('code', $typeCode)->firstOrFail()->id,
            'division_id' => $user->division_id,
            'department_id' => $payload['department_id'] ?? $memo->department_id,
            'memo_request_id' => $memo->id,
            'description' => 'Testing '.$typeCode,
            ...$payload,
        ]);

        $response->assertRedirect();

        return Transaction::where('owner_user_id', $user->id)
            ->where('description', 'Testing '.$typeCode)
            ->latest('id')
            ->firstOrFail();
    }

    private function memoFor(User $user): MemoRequest
    {
        $department = Department::findOrFail($user->department_id);

        return MemoRequest::firstOrCreate(
            ['memo_number' => 'MEMO-TEST-'.$user->id],
            [
                'memo_date' => now()->toDateString(),
                'subject' => 'Memo Test',
                'division_id' => $user->division_id,
                'department_id' => $department->id,
                'created_by' => $user->id,
            ],
        );
    }
}
