<?php

namespace Database\Seeders\InvoiceVerification;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\AccountingVerificationItemStatus;
use App\Modules\InvoiceVerification\Domain\Enums\AccountingVerificationStatus;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalMode;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Enums\AttachmentStatus;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentCode;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceActor;
use App\Modules\InvoiceVerification\Domain\Enums\GeneratedDocumentStatus;
use App\Modules\InvoiceVerification\Domain\Enums\PpaVerificationSheetStatus;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionDocumentStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Models\AccountingVerification;
use App\Modules\InvoiceVerification\Domain\Models\AccountingVerificationItem;
use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalFlow;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\GeneratedDocument;
use App\Modules\InvoiceVerification\Domain\Models\InvoiceMetadata;
use App\Modules\InvoiceVerification\Domain\Models\MemoRequest;
use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet;
use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheetItem;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;
use App\Modules\InvoiceVerification\Domain\Models\TransactionParty;
use App\Modules\InvoiceVerification\Domain\Models\TransactionStatusHistory;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use App\Modules\InvoiceVerification\Domain\Models\VendorDocumentReview;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin.divisi@demo.local')->firstOrFail();
        $accounting = User::query()->where('email', 'akuntansi@demo.local')->firstOrFail();
        $ppaType = TransactionType::query()->where('code', 'PPA')->firstOrFail();

        $registrationNumbers = [
            'TRX/DEMO/2026/0001',
            'TRX/DEMO/2026/0002',
            'TRX/DEMO/2026/0003',
            'TRX/DEMO/2026/0004',
        ];

        Transaction::query()
            ->whereIn('registration_number', $registrationNumbers)
            ->delete();

        $scenarios = [
            [
                'registration_number' => 'TRX/DEMO/2026/0001',
                'invoice_number' => 'INV-DEMO-2026-001',
                'vendor_code' => 'VND-0001',
                'memo_number' => 'MEMO-OPS-2026-001',
                'contract_number' => 'SPK-LRTJ-2026-001',
                'invoice_value' => 18000000,
                'status' => TransactionStatus::DRAFT,
                'step' => TransactionStep::VENDOR_INVOICE_INPUT,
                'mode' => 'draft_vendor_input',
                'description' => 'Demo: draft dibuat Admin dan menunggu Vendor input tagihan serta upload dokumen.',
            ],
            [
                'registration_number' => 'TRX/DEMO/2026/0002',
                'invoice_number' => 'INV-DEMO-2026-002',
                'vendor_code' => 'VND-0002',
                'memo_number' => 'MEMO-OPS-2026-002',
                'contract_number' => 'SPK-LRTJ-2026-002',
                'invoice_value' => 24500000,
                'status' => TransactionStatus::REVISION_IN_PROGRESS,
                'step' => TransactionStep::VENDOR_DOCUMENT_REVIEW,
                'mode' => 'vendor_revision',
                'description' => 'Demo: satu dokumen direject Admin User dan perlu upload ulang vendor.',
            ],
            [
                'registration_number' => 'TRX/DEMO/2026/0003',
                'invoice_number' => 'INV-DEMO-2026-003',
                'vendor_code' => 'VND-0003',
                'memo_number' => 'MEMO-OPS-2026-003',
                'contract_number' => 'SPK-LRTJ-2026-003',
                'invoice_value' => 36750000,
                'status' => TransactionStatus::WAITING_APPROVAL,
                'step' => TransactionStep::KADEP_REVIEW,
                'mode' => 'kadep_review',
                'description' => 'Demo: Admin sudah approve dokumen vendor dan transaksi menunggu Kadep Review.',
            ],
            [
                'registration_number' => 'TRX/DEMO/2026/0004',
                'invoice_number' => 'INV-DEMO-2026-004',
                'vendor_code' => 'VND-0004',
                'memo_number' => 'MEMO-OPS-2026-004',
                'contract_number' => 'SPK-LRTJ-2026-004',
                'invoice_value' => 52900000,
                'status' => TransactionStatus::ACCOUNTING_VERIFICATION,
                'step' => TransactionStep::ACCOUNTING_ADMINISTRATION,
                'mode' => 'accounting_verification',
                'description' => 'Demo: Kadep dan Kadiv sudah approve, transaksi masuk Accounting Verification.',
            ],
        ];

        foreach ($scenarios as $index => $scenario) {
            $this->createDemoTransaction($scenario, $index + 1, $ppaType, $admin, $accounting);
        }
    }

    private function createDemoTransaction(array $scenario, int $sequence, TransactionType $ppaType, User $admin, User $accounting): Transaction
    {
        $vendor = Vendor::query()->where('vendor_code', $scenario['vendor_code'])->firstOrFail();
        $vendorUser = User::query()->where('email', $vendor->contact_email)->firstOrFail();
        $memo = MemoRequest::query()->where('memo_number', $scenario['memo_number'])->firstOrFail();
        $agreement = AgreementReference::query()->where('contract_number', $scenario['contract_number'])->firstOrFail();

        $transaction = Transaction::create([
            'registration_number' => $scenario['registration_number'],
            'transaction_type_id' => $ppaType->id,
            'vendor_id' => $vendor->id,
            'division_id' => $agreement->division_id,
            'department_id' => $agreement->department_id,
            'memo_request_id' => $memo->id,
            'agreement_reference_id' => $agreement->id,
            'title' => $scenario['invoice_number'].' - '.$agreement->contract_number,
            'description' => $scenario['description'],
            'contract_number' => $agreement->contract_number,
            'contract_value' => $agreement->contract_value,
            'status' => $scenario['status'],
            'current_step' => $scenario['step'],
            'created_by' => $admin->id,
            'submitted_at' => $scenario['mode'] === 'draft_vendor_input' ? null : now()->subDays(5 - $sequence),
        ]);

        TransactionParty::create([
            'transaction_id' => $transaction->id,
            'party_type' => 'CREATOR',
            'user_id' => $admin->id,
            'status' => 'ACTIVE',
        ]);

        TransactionParty::create([
            'transaction_id' => $transaction->id,
            'party_type' => 'VENDOR',
            'vendor_id' => $vendor->id,
            'status' => 'ACTIVE',
        ]);

        if ($scenario['mode'] !== 'draft_vendor_input') {
            InvoiceMetadata::create([
                'transaction_id' => $transaction->id,
                'vendor_id' => $vendor->id,
                'invoice_number' => $scenario['invoice_number'],
                'invoice_date' => now()->subDays(8 - $sequence)->toDateString(),
                'account_number' => $vendor->default_account_number,
                'bank_name' => $vendor->defaultBank?->name,
                'memo_number' => $memo->memo_number,
                'contract_number' => $agreement->contract_number,
                'contract_value' => $agreement->contract_value,
                'invoice_value' => $scenario['invoice_value'],
                'ppn_value' => round($scenario['invoice_value'] * 0.11, 2),
                'description' => $scenario['description'],
                'received_date' => now()->subDays(5 - $sequence)->toDateString(),
            ]);
        }

        $documents = $scenario['mode'] === 'draft_vendor_input'
            ? []
            : $this->seedVendorDocuments($transaction, $scenario, $sequence, $vendorUser, $admin);

        if (in_array($scenario['mode'], ['kadep_review', 'accounting_verification'], true)) {
            $this->seedTransactionReviewApprovals($transaction, $scenario['mode']);
        }

        if ($scenario['mode'] === 'accounting_verification') {
            $this->seedAdministrationDocuments($transaction, $admin, $scenario['mode']);
        }

        if ($scenario['mode'] === 'accounting_verification') {
            $this->seedAccountingVerification($transaction, $accounting, $documents);
        }

        TransactionStatusHistory::create([
            'transaction_id' => $transaction->id,
            'to_status' => $scenario['status']->value,
            'to_step' => $scenario['step']->value,
            'changed_by' => $scenario['mode'] === 'draft_vendor_input' ? $admin->id : $vendorUser->id,
            'notes' => 'Demo seed: '.$scenario['description'],
        ]);

        return $transaction;
    }

    private function seedVendorDocuments(Transaction $transaction, array $scenario, int $sequence, User $vendorUser, User $admin): array
    {
        $disk = config('invoice_verification.storage.documents_disk', 'public');
        $documentCodes = [
            DocumentCode::PPA_INVOICE,
            DocumentCode::PPA_KWITANSI,
            DocumentCode::PPA_FAKTUR_PAJAK,
            DocumentCode::PPA_BAPP,
            DocumentCode::PPA_BAST,
            DocumentCode::PPA_LAMPIRAN_PEKERJAAN,
            DocumentCode::PPA_LAPORAN_PEKERJAAN,
        ];
        $documents = [];

        foreach ($documentCodes as $index => $documentCode) {
            $documentType = DocumentType::query()
                ->where('transaction_type_id', $transaction->transaction_type_id)
                ->where('code', $documentCode->value)
                ->firstOrFail();
            $documentNumber = sprintf('%s-%03d/%s/2026', str_replace('PPA_', '', $documentCode->value), $sequence, now()->format('m'));
            $fileName = Str::lower($scenario['invoice_number'].'-'.$documentCode->value).'.pdf';
            $path = 'transactions/'.$transaction->id.'/documents/'.$documentCode->value.'/'.$fileName;

            $this->putDemoPdf($disk, $path, $documentType->name, [
                'Nomor Dokumen: '.$documentNumber,
                'Nomor Invoice: '.$scenario['invoice_number'],
                'Registrasi: '.$transaction->registration_number,
            ]);

            $status = TransactionDocumentStatus::UNDER_REVIEW;

            if ($scenario['mode'] !== 'admin_review') {
                $status = TransactionDocumentStatus::ACCEPTED;
            }

            if ($scenario['mode'] === 'vendor_revision' && $index === 0) {
                $status = TransactionDocumentStatus::REVISION_REQUIRED;
            }

            $document = TransactionDocument::create([
                'transaction_id' => $transaction->id,
                'document_type_id' => $documentType->id,
                'source_actor' => DocumentSourceActor::VENDOR,
                'uploaded_by_user_id' => $vendorUser->id,
                'uploaded_by_vendor_id' => $transaction->vendor_id,
                'document_label' => $documentType->name,
                'document_information_json' => [
                    'document_number' => $documentNumber,
                    'document_date' => now()->subDays(4 - $sequence)->toDateString(),
                    'notes' => 'Dokumen demo lengkap untuk '.$documentType->name,
                ],
                'file_name' => $fileName,
                'file_disk' => $disk,
                'file_path' => $path,
                'file_extension' => 'pdf',
                'mime_type' => 'application/pdf',
                'file_size' => Storage::disk($disk)->size($path),
                'version' => 1,
                'status' => $status,
                'is_latest' => true,
                'uploaded_at' => now()->subDays(4 - $sequence),
            ]);

            if ($status !== TransactionDocumentStatus::UNDER_REVIEW) {
                VendorDocumentReview::create([
                    'transaction_document_id' => $document->id,
                    'reviewed_by' => $admin->id,
                    'status' => $status === TransactionDocumentStatus::ACCEPTED
                        ? 'ACCEPTED'
                        : 'REVISION_REQUIRED',
                    'notes' => $status === TransactionDocumentStatus::REVISION_REQUIRED
                        ? 'Demo seed: dokumen invoice perlu diperbaiki vendor.'
                        : 'Demo seed: dokumen sudah sesuai.',
                    'reviewed_at' => now()->subDays(3 - $sequence),
                ]);
            }

            $documents[] = $document;
        }

        return $documents;
    }

    private function seedAdministrationDocuments(Transaction $transaction, User $admin, string $mode): void
    {
        $disk = config('invoice_verification.storage.documents_disk', 'public');
        $generatedPath = 'transactions/'.$transaction->id.'/generated/lembar-ppa.pdf';
        $this->putDemoPdf($disk, $generatedPath, 'Lembar PPA', [
            'Registrasi: '.$transaction->registration_number,
            'Vendor: '.$transaction->vendor?->name,
            'Invoice: '.$transaction->invoiceMetadata?->invoice_number,
        ]);

        $generatedDocument = GeneratedDocument::create([
            'transaction_id' => $transaction->id,
            'document_code' => DocumentCode::PPA_LEMBAR_AWAL->value,
            'document_number' => $transaction->registration_number,
            'file_name' => 'lembar-ppa.pdf',
            'file_disk' => $disk,
            'file_path' => $generatedPath,
            'version' => 1,
            'approval_mode' => ApprovalMode::MAIN_FLOW,
            'generation_status' => GeneratedDocumentStatus::GENERATED,
            'generated_by' => $admin->id,
            'generated_at' => now()->subDay(),
        ]);

        $sheetPath = 'transactions/'.$transaction->id.'/generated/lembar-checklist-ppa.pdf';
        $this->putDemoPdf($disk, $sheetPath, 'Lembar Checklist PPA', [
            'Registrasi: '.$transaction->registration_number,
            'Checklist: Semua dokumen tagihan vendor terlampir',
        ]);

        $sheet = PpaVerificationSheet::create([
            'transaction_id' => $transaction->id,
            'status' => $mode === 'accounting_verification'
                ? PpaVerificationSheetStatus::APPROVED
                : PpaVerificationSheetStatus::SUBMITTED,
            'filled_by_user_id' => $admin->id,
            'submitted_at' => now()->subDay(),
            'approved_by_user_id' => $mode === 'accounting_verification' ? $admin->id : null,
            'approved_at' => $mode === 'accounting_verification' ? now()->subHours(6) : null,
            'file_name' => 'lembar-checklist-ppa.pdf',
            'file_disk' => $disk,
            'file_path' => $sheetPath,
        ]);

        DocumentType::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->whereIn('code', [
                DocumentCode::PPA_INVOICE->value,
                DocumentCode::PPA_KWITANSI->value,
                DocumentCode::PPA_FAKTUR_PAJAK->value,
                DocumentCode::PPA_BAPP->value,
                DocumentCode::PPA_BAST->value,
                DocumentCode::PPA_MEMO_PERMOHONAN->value,
                DocumentCode::PPA_PERJANJIAN->value,
                DocumentCode::PPA_LAMPIRAN_PEKERJAAN->value,
                DocumentCode::PPA_LAPORAN_PEKERJAAN->value,
            ])
            ->get()
            ->each(function (DocumentType $documentType) use ($sheet) {
                PpaVerificationSheetItem::create([
                    'verification_sheet_id' => $sheet->id,
                    'document_type_id' => $documentType->id,
                    'attachment_status' => AttachmentStatus::ATTACHED,
                    'notes' => 'Demo seed: dokumen tersedia.',
                ]);
            });

        unset($generatedDocument);
    }

    private function seedTransactionReviewApprovals(Transaction $transaction, string $mode): void
    {
        foreach ([
            [1, RoleCode::KEPALA_DEPARTEMEN, 'Kepala Departemen'],
            [2, RoleCode::KEPALA_DIVISI, 'Kepala Divisi'],
        ] as [$stepNo, $roleCode, $stepName]) {
            $flow = ApprovalFlow::updateOrCreate(
                [
                    'transaction_type_id' => $transaction->transaction_type_id,
                    'document_code' => 'TRANSACTION_REVIEW',
                    'step_no' => $stepNo,
                ],
                [
                    'step_code' => $roleCode->value,
                    'step_name' => $stepName,
                    'is_required' => true,
                ],
            );
            $approver = $this->resolveApprover($flow->step_code, $transaction);
            $isAccountingScenario = $mode === 'accounting_verification';

            ApprovalTransaction::create([
                'transaction_id' => $transaction->id,
                'generated_document_id' => null,
                'approval_flow_id' => $flow->id,
                'approver_user_id' => $approver->id,
                'status' => $isAccountingScenario ? ApprovalStatus::APPROVED : ApprovalStatus::PENDING,
                'notes' => $isAccountingScenario ? 'Demo seed: sudah disetujui.' : null,
                'action_at' => $isAccountingScenario ? now()->subHours(8 - $flow->step_no) : null,
            ]);
        }
    }

    private function seedAccountingVerification(Transaction $transaction, User $accounting, array $documents): void
    {
        $verification = AccountingVerification::create([
            'transaction_id' => $transaction->id,
            'verifier_user_id' => $accounting->id,
            'status' => AccountingVerificationStatus::IN_PROGRESS,
            'notes' => 'Demo seed: siap diverifikasi accounting.',
        ]);

        foreach ($documents as $document) {
            AccountingVerificationItem::create([
                'accounting_verification_id' => $verification->id,
                'transaction_document_id' => $document->id,
                'status' => AccountingVerificationItemStatus::VALID,
            ]);
        }
    }

    private function resolveApprover(string $roleCode, Transaction $transaction): User
    {
        $query = User::query()
            ->where('role_code', $roleCode)
            ->where('is_active', true);

        if ($roleCode === RoleCode::KEPALA_DEPARTEMEN->value) {
            $query->where('department_id', $transaction->department_id);
        } else {
            $query->where('division_id', $transaction->division_id);
        }

        return $query->first()
            ?? User::query()->where('role_code', $roleCode)->firstOrFail();
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
