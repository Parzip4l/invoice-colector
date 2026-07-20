<?php

namespace Database\Seeders\InvoiceVerification;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\AccountingVerificationItemStatus;
use App\Modules\InvoiceVerification\Domain\Enums\AccountingVerificationStatus;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceActor;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceType;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionDocumentStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use App\Modules\InvoiceVerification\Domain\Models\AccountingVerification;
use App\Modules\InvoiceVerification\Domain\Models\AccountingVerificationItem;
use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\InvoiceMetadata;
use App\Modules\InvoiceVerification\Domain\Models\MemoRequest;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;
use App\Modules\InvoiceVerification\Domain\Models\TransactionParty;
use App\Modules\InvoiceVerification\Domain\Models\TransactionStatusHistory;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $externalVendorUser = User::query()->where('email', 'vendor@demo.local')->firstOrFail();
        $internalVendorUser = User::query()->where('email', 'user.divisi@demo.local')->firstOrFail();
        $accounting = User::query()->where('email', 'akuntansi@demo.local')->firstOrFail();
        $finance = User::query()->where('email', 'finance@demo.local')->firstOrFail();

        $this->deleteExistingDemoTransactions();

        $ppaDraft = $this->createPpaContract(
            registrationNumber: 'PPA-00001',
            status: TransactionStatus::DRAFT,
            owner: $externalVendorUser,
            titleSuffix: 'Draft vendor eksternal',
        );

        $ppaSubmitted = $this->createPpaContract(
            registrationNumber: 'PPA-00002',
            status: TransactionStatus::SUBMITTED,
            owner: $externalVendorUser,
            titleSuffix: 'Menunggu review Accounting',
        );

        $pnkInReview = $this->createInternalTransaction(
            registrationNumber: 'PNK-00001',
            typeCode: TransactionTypeCode::PPA_NON_CONTRACT,
            status: TransactionStatus::IN_REVIEW,
            owner: $internalVendorUser,
            title: 'Pengadaan perlengkapan administrasi operasional',
            amount: 18500000,
        );

        $spuNotApproved = $this->createInternalTransaction(
            registrationNumber: 'SPU-00001',
            typeCode: TransactionTypeCode::SPU,
            status: TransactionStatus::NOT_APPROVED,
            owner: $internalVendorUser,
            title: 'Uang muka kegiatan inspeksi jalur',
            amount: 12500000,
            accountingUser: $accounting,
            accountingHasIssue: true,
        );

        $spuReceived = $this->createInternalTransaction(
            registrationNumber: 'SPU-00002',
            typeCode: TransactionTypeCode::SPU,
            status: TransactionStatus::RECEIVED,
            owner: $internalVendorUser,
            title: 'Uang muka kegiatan kalibrasi perangkat',
            amount: 20000000,
            accountingUser: $accounting,
        );

        $this->createSpuk(
            registrationNumber: 'SPUK-00001',
            status: TransactionStatus::SCHEDULING_PAYMENT,
            owner: $internalVendorUser,
            parentSpu: $spuReceived,
            accountabilityAmount: 16500000,
            accountingUser: $accounting,
            financeUser: $finance,
        );

        $this->createPettyCash(
            registrationNumber: 'KK-00001',
            status: TransactionStatus::PAID,
            owner: $internalVendorUser,
            remainingAmount: 3500000,
            accountingUser: $accounting,
            financeUser: $finance,
        );

        $this->createAdditionalDemoTransactions($externalVendorUser, $internalVendorUser, $accounting, $finance, $spuReceived);

        $this->seedNumberSequences([
            'PPA' => 8,
            'PNK' => 6,
            'SPU' => 8,
            'SPUK' => 4,
            'KK' => 4,
        ]);

        unset($ppaDraft, $ppaSubmitted, $pnkInReview);
    }

    private function createAdditionalDemoTransactions(
        User $externalVendorUser,
        User $internalVendorUser,
        User $accounting,
        User $finance,
        Transaction $parentSpu,
    ): void {
        $ppaScenarios = [
            ['PPA-00003', TransactionStatus::IN_REVIEW, 'Review dokumen invoice', false],
            ['PPA-00004', TransactionStatus::NOT_APPROVED, 'Revisi faktur pajak', true],
            ['PPA-00005', TransactionStatus::RECEIVED, 'Dokumen lengkap', false],
            ['PPA-00006', TransactionStatus::SCHEDULING_PAYMENT, 'Menunggu jadwal bayar', false],
            ['PPA-00007', TransactionStatus::PAID, 'Pembayaran selesai', false],
            ['PPA-00008', TransactionStatus::DRAFT, 'Draft pekerjaan tambahan', false],
        ];

        foreach ($ppaScenarios as [$number, $status, $suffix, $hasIssue]) {
            $transaction = $this->createPpaContract($number, $status, $externalVendorUser, $suffix, $hasIssue);
            $this->seedFinanceStateIfNeeded($transaction, $status, $finance);
        }

        foreach ([
            ['PNK-00002', TransactionStatus::DRAFT, 'Pengadaan ATK kantor pusat', 8500000, false],
            ['PNK-00003', TransactionStatus::SUBMITTED, 'Kegiatan koordinasi operasional', 12300000, false],
            ['PNK-00004', TransactionStatus::NOT_APPROVED, 'Pengadaan konsumsi pelatihan', 9750000, true],
            ['PNK-00005', TransactionStatus::RECEIVED, 'Jasa dokumentasi kegiatan', 14600000, false],
            ['PNK-00006', TransactionStatus::PAID, 'Sewa perlengkapan rapat', 11250000, false],
        ] as [$number, $status, $title, $amount, $hasIssue]) {
            $transaction = $this->createInternalTransaction(
                registrationNumber: $number,
                typeCode: TransactionTypeCode::PPA_NON_CONTRACT,
                status: $status,
                owner: $internalVendorUser,
                title: $title,
                amount: $amount,
                accountingUser: $accounting,
                accountingHasIssue: $hasIssue,
            );
            $this->seedFinanceStateIfNeeded($transaction, $status, $finance);
        }

        foreach ([
            ['SPU-00003', TransactionStatus::DRAFT, 'Uang muka survey jalur', 7000000],
            ['SPU-00004', TransactionStatus::SUBMITTED, 'Uang muka inspeksi depo', 15500000],
            ['SPU-00005', TransactionStatus::IN_REVIEW, 'Uang muka workshop safety', 9500000],
            ['SPU-00006', TransactionStatus::RECEIVED, 'Uang muka maintenance lift', 22500000],
            ['SPU-00007', TransactionStatus::SCHEDULING_PAYMENT, 'Uang muka perbaikan panel', 17800000],
            ['SPU-00008', TransactionStatus::PAID, 'Uang muka audit perangkat', 13200000],
        ] as [$number, $status, $title, $amount]) {
            $transaction = $this->createInternalTransaction(
                registrationNumber: $number,
                typeCode: TransactionTypeCode::SPU,
                status: $status,
                owner: $internalVendorUser,
                title: $title,
                amount: $amount,
                accountingUser: $accounting,
            );
            $this->seedFinanceStateIfNeeded($transaction, $status, $finance);
        }

        foreach ([
            ['SPUK-00002', TransactionStatus::SUBMITTED, 4500000],
            ['SPUK-00003', TransactionStatus::RECEIVED, 9200000],
            ['SPUK-00004', TransactionStatus::PAID, 12800000],
        ] as [$number, $status, $accountabilityAmount]) {
            $transaction = $this->createSpuk(
                registrationNumber: $number,
                status: $status,
                owner: $internalVendorUser,
                parentSpu: $parentSpu,
                accountabilityAmount: $accountabilityAmount,
                accountingUser: $accounting,
                financeUser: $finance,
            );
            $this->seedFinanceStateIfNeeded($transaction, $status, $finance);
        }

        foreach ([
            ['KK-00002', TransactionStatus::SUBMITTED, 9200000],
            ['KK-00003', TransactionStatus::RECEIVED, 6200000],
            ['KK-00004', TransactionStatus::SCHEDULING_PAYMENT, 4800000],
        ] as [$number, $status, $remainingAmount]) {
            $transaction = $this->createPettyCash(
                registrationNumber: $number,
                status: $status,
                owner: $internalVendorUser,
                remainingAmount: $remainingAmount,
                accountingUser: $accounting,
                financeUser: $finance,
            );
            $this->seedFinanceStateIfNeeded($transaction, $status, $finance);
        }
    }

    private function deleteExistingDemoTransactions(): void
    {
        Transaction::query()
            ->where(function ($query) {
                $query->where('registration_number', 'like', 'PPA-0000%')
                    ->orWhere('registration_number', 'like', 'PNK-0000%')
                    ->orWhere('registration_number', 'like', 'SPU-0000%')
                    ->orWhere('registration_number', 'like', 'SPUK-0000%')
                    ->orWhere('registration_number', 'like', 'KK-0000%')
                    ->orWhere('registration_number', 'like', 'TRX/DEMO/%');
            })
            ->delete();
    }

    private function createPpaContract(
        string $registrationNumber,
        TransactionStatus $status,
        User $owner,
        string $titleSuffix,
        bool $accountingHasIssue = false,
    ): Transaction {
        $vendor = Vendor::query()->where('contact_email', $owner->email)->firstOrFail();
        $agreement = AgreementReference::query()->where('vendor_id', $vendor->id)->oldest()->firstOrFail();
        $memo = MemoRequest::query()->where('memo_number', 'MEMO-OPS-2026-001')->firstOrFail();
        $type = $this->type(TransactionTypeCode::PPA);

        $transaction = Transaction::create([
            'registration_number' => $registrationNumber,
            'transaction_type_id' => $type->id,
            'vendor_id' => $vendor->id,
            'owner_user_id' => $owner->id,
            'division_id' => $agreement->division_id,
            'department_id' => $agreement->department_id,
            'memo_request_id' => $memo->id,
            'agreement_reference_id' => $agreement->id,
            'title' => $agreement->title.' - '.$titleSuffix,
            'description' => 'Seeder workflow baru PPA Kontrak.',
            'contract_number' => $agreement->contract_number,
            'contract_value' => $agreement->contract_value,
            'period' => '2026-07',
            'status' => $status,
            'current_step' => $this->stepFor($status),
            'created_by' => $owner->id,
            'submitted_at' => $status === TransactionStatus::DRAFT ? null : now()->subDays(5),
        ]);

        $this->attachParties($transaction, $owner, $vendor);
        $this->seedInvoiceMetadata($transaction, (int) $agreement->contract_value);
        $this->seedDocuments($transaction, $owner, $vendor, $status);
        $this->seedWorkflowArtifacts($transaction, $status, null, $accountingHasIssue);

        return $transaction;
    }

    private function createInternalTransaction(
        string $registrationNumber,
        TransactionTypeCode $typeCode,
        TransactionStatus $status,
        User $owner,
        string $title,
        int $amount,
        ?User $accountingUser = null,
        bool $accountingHasIssue = false,
    ): Transaction {
        $memo = MemoRequest::query()->where('memo_number', 'MEMO-OPS-2026-002')->firstOrFail();
        $type = $this->type($typeCode);

        $transaction = Transaction::create([
            'registration_number' => $registrationNumber,
            'transaction_type_id' => $type->id,
            'owner_user_id' => $owner->id,
            'division_id' => $owner->division_id,
            'department_id' => $owner->department_id,
            'memo_request_id' => $memo->id,
            'title' => $title,
            'activity_name' => $title,
            'description' => 'Seeder workflow baru '.$type->name.'.',
            'transaction_bank_name' => 'Bank Mandiri',
            'transaction_account_number' => '8877665544',
            'spu_amount' => $typeCode === TransactionTypeCode::SPU ? $amount : null,
            'contract_value' => $typeCode === TransactionTypeCode::PPA_NON_CONTRACT ? $amount : null,
            'status' => $status,
            'current_step' => $this->stepFor($status),
            'created_by' => $owner->id,
            'submitted_at' => $status === TransactionStatus::DRAFT ? null : now()->subDays(4),
        ]);

        $this->attachParties($transaction, $owner);
        $this->seedInvoiceMetadata($transaction, $amount);
        $this->seedDocuments($transaction, $owner, null, $status);
        $this->seedWorkflowArtifacts($transaction, $status, $accountingUser, $accountingHasIssue);

        return $transaction;
    }

    private function createSpuk(
        string $registrationNumber,
        TransactionStatus $status,
        User $owner,
        Transaction $parentSpu,
        int $accountabilityAmount,
        User $accountingUser,
        User $financeUser,
    ): Transaction {
        $type = $this->type(TransactionTypeCode::SPUK);
        $remainingAmount = (int) $parentSpu->spu_amount - $accountabilityAmount;

        $transaction = Transaction::create([
            'registration_number' => $registrationNumber,
            'transaction_type_id' => $type->id,
            'owner_user_id' => $owner->id,
            'parent_spu_transaction_id' => $parentSpu->id,
            'division_id' => $owner->division_id,
            'department_id' => $owner->department_id,
            'memo_request_id' => $parentSpu->memo_request_id,
            'title' => 'Pertanggungjawaban '.$parentSpu->activity_name,
            'activity_name' => $parentSpu->activity_name,
            'description' => 'Seeder workflow baru SPUK.',
            'spu_amount' => $parentSpu->spu_amount,
            'accountability_amount' => $accountabilityAmount,
            'remaining_amount' => $remainingAmount,
            'status' => $status,
            'current_step' => $this->stepFor($status),
            'created_by' => $owner->id,
            'submitted_at' => now()->subDays(4),
            'scheduled_payment_at' => in_array($status, [TransactionStatus::SCHEDULING_PAYMENT, TransactionStatus::PAID], true) ? now()->addDays(2) : null,
        ]);

        $this->attachParties($transaction, $owner);
        $this->seedInvoiceMetadata($transaction, $accountabilityAmount);
        $this->seedDocuments($transaction, $owner, null, $status);
        $this->seedWorkflowArtifacts($transaction, $status, $accountingUser);
        $this->seedFinanceStateIfNeeded($transaction, $status, $financeUser);

        return $transaction;
    }

    private function createPettyCash(
        string $registrationNumber,
        TransactionStatus $status,
        User $owner,
        int $remainingAmount,
        User $accountingUser,
        User $financeUser,
    ): Transaction {
        $type = $this->type(TransactionTypeCode::KAS_KECIL);
        $ceiling = (int) ($owner->division?->petty_cash_ceiling ?? 0);
        $topUp = $ceiling - $remainingAmount;

        $transaction = Transaction::create([
            'registration_number' => $registrationNumber,
            'transaction_type_id' => $type->id,
            'owner_user_id' => $owner->id,
            'division_id' => $owner->division_id,
            'department_id' => $owner->department_id,
            'title' => 'Top Up Kas Kecil Divisi Operasional',
            'activity_name' => 'Top Up Kas Kecil Divisi Operasional',
            'description' => 'Seeder workflow baru Kas Kecil.',
            'period' => '2026-07',
            'petty_cash_ceiling_snapshot' => $ceiling,
            'petty_cash_remaining_amount' => $remainingAmount,
            'petty_cash_top_up_amount' => $topUp,
            'status' => $status,
            'current_step' => $this->stepFor($status),
            'created_by' => $owner->id,
            'submitted_at' => now()->subDays(8),
            'scheduled_payment_at' => in_array($status, [TransactionStatus::SCHEDULING_PAYMENT, TransactionStatus::PAID], true) ? now()->subDays(2) : null,
            'paid_at' => $status === TransactionStatus::PAID ? now()->subDay() : null,
        ]);

        $this->attachParties($transaction, $owner);
        $this->seedInvoiceMetadata($transaction, $topUp);
        $this->seedDocuments($transaction, $owner, null, $status);
        $this->seedWorkflowArtifacts($transaction, $status, $accountingUser);
        $this->seedFinanceStateIfNeeded($transaction, $status, $financeUser);

        return $transaction;
    }

    private function seedInvoiceMetadata(Transaction $transaction, int $amount): void
    {
        InvoiceMetadata::updateOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'vendor_id' => $transaction->vendor_id,
                'invoice_number' => 'INV-'.$transaction->registration_number,
                'invoice_date' => now()->subDays(3)->toDateString(),
                'received_date' => in_array($transaction->status, [
                    TransactionStatus::RECEIVED,
                    TransactionStatus::SCHEDULING_PAYMENT,
                    TransactionStatus::PAID,
                ], true) ? now()->subDays(2)->toDateString() : null,
                'account_number' => $transaction->transaction_account_number ?: '1234567890',
                'account_name' => $transaction->vendor?->name ?? $transaction->owner?->name ?? 'User Divisi Demo',
                'bank_name' => $transaction->transaction_bank_name ?: 'Bank Mandiri',
                'memo_number' => $transaction->memoRequest?->memo_number,
                'contract_number' => $transaction->contract_number,
                'contract_value' => $transaction->contract_value,
                'invoice_value' => $amount,
                'ppn_value' => (int) round($amount * 0.11),
                'description' => $transaction->description,
            ],
        );
    }

    private function seedFinanceStateIfNeeded(Transaction $transaction, TransactionStatus $status, User $financeUser): void
    {
        if (! in_array($status, [TransactionStatus::SCHEDULING_PAYMENT, TransactionStatus::PAID], true)) {
            return;
        }

        $transaction->forceFill([
            'scheduled_payment_at' => $transaction->scheduled_payment_at ?? now()->addDays($status === TransactionStatus::PAID ? -2 : 2),
            'paid_at' => $status === TransactionStatus::PAID ? ($transaction->paid_at ?? now()->subDay()) : null,
        ])->save();

        $this->seedFinanceProof($transaction, $financeUser, paid: $status === TransactionStatus::PAID);
    }

    private function attachParties(Transaction $transaction, User $owner, ?Vendor $vendor = null): void
    {
        TransactionParty::create([
            'transaction_id' => $transaction->id,
            'party_type' => 'CREATOR',
            'user_id' => $owner->id,
            'status' => 'ACTIVE',
        ]);

        if ($vendor) {
            TransactionParty::create([
                'transaction_id' => $transaction->id,
                'party_type' => 'VENDOR',
                'vendor_id' => $vendor->id,
                'status' => 'ACTIVE',
            ]);
        }
    }

    private function seedDocuments(Transaction $transaction, User $uploader, ?Vendor $vendor, TransactionStatus $status): Collection
    {
        if ($status === TransactionStatus::DRAFT) {
            return collect();
        }

        $disk = config('invoice_verification.storage.documents_disk', 'public');
        $documents = collect();

        DocumentType::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('source_type', DocumentSourceType::VENDOR->value)
            ->orderBy('sort_order')
            ->get()
            ->each(function (DocumentType $documentType) use ($transaction, $uploader, $vendor, $status, $disk, $documents) {
                $fileName = Str::lower($transaction->registration_number.'-'.$documentType->code).'.pdf';
                $path = 'transactions/'.$transaction->id.'/documents/'.$documentType->code.'/'.$fileName;

                $this->putDemoPdf($disk, $path, $documentType->name, [
                    'Nomor Transaksi: '.$transaction->registration_number,
                    'Status: '.$transaction->status->label(),
                    'Seeder: workflow baru Invoice Collector',
                ]);

                $documentStatus = $status === TransactionStatus::NOT_APPROVED
                    ? TransactionDocumentStatus::REVISION_REQUIRED
                    : TransactionDocumentStatus::ACCEPTED;

                if (in_array($status, [TransactionStatus::SUBMITTED, TransactionStatus::IN_REVIEW], true)) {
                    $documentStatus = TransactionDocumentStatus::UPLOADED;
                }

                $documents->push(TransactionDocument::create([
                    'transaction_id' => $transaction->id,
                    'document_type_id' => $documentType->id,
                    'source_actor' => DocumentSourceActor::VENDOR,
                    'uploaded_by_user_id' => $uploader->id,
                    'uploaded_by_vendor_id' => $vendor?->id,
                    'document_label' => $documentType->name,
                    'document_information_json' => [
                        'document_number' => $transaction->registration_number.'/'.$documentType->code,
                        'document_date' => now()->subDays(3)->toDateString(),
                        'notes' => 'Dokumen demo untuk workflow baru.',
                    ],
                    'file_name' => $fileName,
                    'file_disk' => $disk,
                    'file_path' => $path,
                    'file_extension' => 'pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => Storage::disk($disk)->size($path),
                    'version' => 1,
                    'status' => $documentStatus,
                    'is_latest' => true,
                    'uploaded_at' => now()->subDays(3),
                ]));
            });

        return $documents;
    }

    private function seedWorkflowArtifacts(
        Transaction $transaction,
        TransactionStatus $status,
        ?User $accountingUser = null,
        bool $hasIssue = false,
    ): void {
        $this->seedStatusHistory($transaction);

        if (! in_array($status, [
            TransactionStatus::IN_REVIEW,
            TransactionStatus::NOT_APPROVED,
            TransactionStatus::RECEIVED,
            TransactionStatus::SCHEDULING_PAYMENT,
            TransactionStatus::PAID,
        ], true)) {
            return;
        }

        $accountingUser ??= User::query()->where('email', 'akuntansi@demo.local')->firstOrFail();
        $verificationStatus = match ($status) {
            TransactionStatus::IN_REVIEW => AccountingVerificationStatus::IN_PROGRESS,
            TransactionStatus::NOT_APPROVED => AccountingVerificationStatus::REVISION_REQUIRED,
            default => AccountingVerificationStatus::COMPLETED,
        };

        $verification = AccountingVerification::create([
            'transaction_id' => $transaction->id,
            'verifier_user_id' => $accountingUser->id,
            'status' => $verificationStatus,
            'notes' => $hasIssue
                ? 'Seeder: dokumen perlu direvisi vendor.'
                : 'Seeder: dokumen lengkap sesuai workflow baru.',
            'verified_at' => $status === TransactionStatus::IN_REVIEW ? null : now()->subDays(2),
        ]);

        $transaction->documents()->where('is_latest', true)->get()->each(function (TransactionDocument $document) use ($verification, $hasIssue) {
            AccountingVerificationItem::create([
                'accounting_verification_id' => $verification->id,
                'transaction_document_id' => $document->id,
                'status' => $hasIssue ? AccountingVerificationItemStatus::REVISION_REQUIRED : AccountingVerificationItemStatus::VALID,
                'notes' => $hasIssue ? 'Seeder: dokumen belum lengkap.' : 'Seeder: dokumen valid.',
                'verified_at' => $verification->verified_at,
            ]);
        });
    }

    private function seedFinanceProof(Transaction $transaction, User $financeUser, bool $paid): void
    {
        $disk = config('invoice_verification.storage.documents_disk', 'public');
        $fileName = Str::lower($transaction->registration_number.'-bukti-transfer.pdf');
        $path = 'transactions/'.$transaction->id.'/payment-proof/'.$fileName;

        $this->putDemoPdf($disk, $path, 'Bukti Transfer Pembayaran', [
            'Nomor Transaksi: '.$transaction->registration_number,
            'Status: '.($paid ? 'Paid' : 'Scheduling Payment'),
        ]);

        $transaction->forceFill([
            'payment_proof_file_name' => $fileName,
            'payment_proof_file_disk' => $disk,
            'payment_proof_file_path' => $path,
            'payment_proof_mime_type' => 'application/pdf',
            'payment_proof_file_size' => Storage::disk($disk)->size($path),
            'payment_proof_uploaded_by' => $financeUser->id,
            'payment_proof_uploaded_at' => now()->subDay(),
        ])->save();
    }

    private function seedStatusHistory(Transaction $transaction): void
    {
        $statuses = match ($transaction->status) {
            TransactionStatus::DRAFT => [TransactionStatus::DRAFT],
            TransactionStatus::SUBMITTED => [TransactionStatus::DRAFT, TransactionStatus::SUBMITTED],
            TransactionStatus::IN_REVIEW => [TransactionStatus::DRAFT, TransactionStatus::SUBMITTED, TransactionStatus::IN_REVIEW],
            TransactionStatus::NOT_APPROVED => [TransactionStatus::DRAFT, TransactionStatus::SUBMITTED, TransactionStatus::IN_REVIEW, TransactionStatus::NOT_APPROVED],
            TransactionStatus::RECEIVED => [TransactionStatus::DRAFT, TransactionStatus::SUBMITTED, TransactionStatus::IN_REVIEW, TransactionStatus::RECEIVED],
            TransactionStatus::SCHEDULING_PAYMENT => [TransactionStatus::DRAFT, TransactionStatus::SUBMITTED, TransactionStatus::IN_REVIEW, TransactionStatus::RECEIVED, TransactionStatus::SCHEDULING_PAYMENT],
            TransactionStatus::PAID => [TransactionStatus::DRAFT, TransactionStatus::SUBMITTED, TransactionStatus::IN_REVIEW, TransactionStatus::RECEIVED, TransactionStatus::SCHEDULING_PAYMENT, TransactionStatus::PAID],
            default => [$transaction->status],
        };

        foreach ($statuses as $index => $status) {
            TransactionStatusHistory::create([
                'transaction_id' => $transaction->id,
                'from_status' => $index === 0 ? null : $statuses[$index - 1]->value,
                'to_status' => $status->value,
                'from_step' => $index === 0 ? null : $this->stepFor($statuses[$index - 1])->value,
                'to_step' => $this->stepFor($status)->value,
                'changed_by' => $transaction->owner_user_id ?? $transaction->created_by,
                'notes' => 'Seeder workflow baru: '.$status->label(),
                'created_at' => now()->subDays(max(0, count($statuses) - $index)),
            ]);
        }
    }

    private function seedNumberSequences(array $prefixes): void
    {
        foreach ($prefixes as $prefix => $lastNumber) {
            DB::table('transaction_number_sequences')->updateOrInsert(
                ['prefix' => $prefix],
                [
                    'last_number' => $lastNumber,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function type(TransactionTypeCode $code): TransactionType
    {
        return TransactionType::query()->where('code', $code->value)->firstOrFail();
    }

    private function stepFor(TransactionStatus $status): TransactionStep
    {
        return match ($status) {
            TransactionStatus::DRAFT => TransactionStep::VENDOR_INVOICE_INPUT,
            TransactionStatus::SUBMITTED => TransactionStep::ACCOUNTING_ADMINISTRATION,
            TransactionStatus::IN_REVIEW,
            TransactionStatus::NOT_APPROVED,
            TransactionStatus::RECEIVED => TransactionStep::ACCOUNTING_VERIFICATION,
            TransactionStatus::SCHEDULING_PAYMENT,
            TransactionStatus::PAID => TransactionStep::FINANCE_PROCESS,
            default => TransactionStep::VENDOR_INVOICE_INPUT,
        };
    }

    private function putDemoPdf(string $disk, string $path, string $title, array $lines): void
    {
        Storage::disk($disk)->put($path, $this->demoPdfContent($title, $lines));
    }

    private function demoPdfContent(string $title, array $lines): string
    {
        $text = collect([$title, ...$lines])
            ->map(fn (string $line, int $index) => sprintf('BT /F1 12 Tf 72 %d Td (%s) Tj ET', 760 - ($index * 22), $this->escapePdfText($line)))
            ->implode("\n");

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length ".strlen($text)." >>\nstream\n".$text."\nendstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xrefOffset."\n%%EOF\n";

        return $pdf;
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
