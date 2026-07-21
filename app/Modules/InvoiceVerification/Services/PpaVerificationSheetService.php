<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\AttachmentStatus;
use App\Modules\InvoiceVerification\Domain\Enums\PpaVerificationSheetStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet;
use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheetItem;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Services\Contracts\PpaVerificationSheetPdfGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PpaVerificationSheetService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected TransactionLifecycleService $transactionLifecycleService,
        protected PpaVerificationSheetPdfGenerator $pdfGenerator,
    ) {
    }

    public function getOrCreate(Transaction $transaction, User $actor): PpaVerificationSheet
    {
        $sheet = PpaVerificationSheet::firstOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'status' => PpaVerificationSheetStatus::DRAFT,
                'filled_by_user_id' => $actor->id,
            ],
        );

        $checklistTypes = DocumentType::query()
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->whereIn('code', [
                'PPA_INVOICE',
                'PPA_KWITANSI',
                'PPA_FAKTUR_PAJAK',
                'PPA_BAPP',
                'PPA_BAST',
                'PPA_MEMO_PERMOHONAN',
                'PPA_PERJANJIAN',
                'PPA_LAMPIRAN_PEKERJAAN',
                'PPA_LAPORAN_PEKERJAAN',
            ])
            ->orderBy('sort_order')
            ->get();

        foreach ($checklistTypes as $documentType) {
            PpaVerificationSheetItem::firstOrCreate(
                [
                    'verification_sheet_id' => $sheet->id,
                    'document_type_id' => $documentType->id,
                ],
                [
                    'attachment_status' => AttachmentStatus::NOT_ATTACHED,
                ],
            );
        }

        return $sheet->fresh('items.documentType', 'transaction.latestDocuments.documentType');
    }

    public function saveChecklist(Transaction $transaction, User $actor, array $items): PpaVerificationSheet
    {
        return DB::transaction(function () use ($transaction, $actor, $items) {
            $sheet = $this->getOrCreate($transaction, $actor);

            foreach ($items as $item) {
                PpaVerificationSheetItem::query()
                    ->where('verification_sheet_id', $sheet->id)
                    ->where('document_type_id', $item['document_type_id'])
                    ->update([
                        'attachment_status' => $item['attachment_status'],
                        'notes' => $item['notes'] ?? null,
                    ]);
            }

            $sheet->update([
                'filled_by_user_id' => $actor->id,
                'status' => PpaVerificationSheetStatus::DRAFT,
            ]);

            $this->auditLogService->log(
                module: 'ppa-verification-sheet',
                action: 'update_transaction',
                actor: $actor,
                transaction: $transaction,
                referenceType: PpaVerificationSheet::class,
                referenceId: $sheet->id,
                newValue: ['items_count' => count($items)],
            );

            return $sheet->fresh('items.documentType');
        });
    }

    public function submit(Transaction $transaction, User $actor): PpaVerificationSheet
    {
        $sheet = $this->getOrCreate($transaction, $actor);
        $items = $sheet->items()->with('documentType')->get();

        if ($items->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Checklist verifikasi PPA belum diisi.',
            ]);
        }

        foreach ($items as $item) {
            if ($item->attachment_status === AttachmentStatus::ATTACHED
                && ! $this->hasRegisteredOrUploadedDocument($transaction, $item->documentType)) {
                throw ValidationException::withMessages([
                    'items' => sprintf('Dokumen %s ditandai ATTACHED namun file belum diunggah.', $item->documentType->name),
                ]);
            }
        }

        $sheet->update([
            'status' => PpaVerificationSheetStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $pdf = $this->pdfGenerator->generate($sheet->fresh('transaction', 'items.documentType'));
        $sheet->update($pdf);

        $this->transactionLifecycleService->transition(
            $transaction,
            TransactionStatus::DOCUMENT_COLLECTION,
            TransactionStep::INTERNAL_DOCUMENT_UPLOAD,
            $actor,
            'Lembar verifikasi PPA disubmit. Transaksi lanjut ke pengumpulan dokumen internal.',
        );

        $this->auditLogService->log(
            module: 'ppa-verification-sheet',
            action: 'submit_approval',
            actor: $actor,
            transaction: $transaction,
            referenceType: PpaVerificationSheet::class,
            referenceId: $sheet->id,
            newValue: ['status' => PpaVerificationSheetStatus::SUBMITTED->value],
        );

        return $sheet->fresh('items.documentType');
    }

    public function generateFromAcceptedDocuments(Transaction $transaction, User $actor): PpaVerificationSheet
    {
        return DB::transaction(function () use ($transaction, $actor) {
            $sheet = $this->getOrCreate($transaction, $actor);

            foreach ($sheet->items()->with('documentType')->get() as $item) {
                $item->update([
                    'attachment_status' => $this->hasRegisteredOrUploadedDocument($transaction, $item->documentType)
                        ? AttachmentStatus::ATTACHED
                        : AttachmentStatus::NOT_ATTACHED,
                    'notes' => null,
                ]);
            }

            $sheet->update([
                'status' => PpaVerificationSheetStatus::SUBMITTED,
                'filled_by_user_id' => $actor->id,
                'submitted_at' => now(),
            ]);

            $pdf = $this->pdfGenerator->generate($sheet->fresh('transaction', 'items.documentType'));
            $sheet->update($pdf);

            $this->auditLogService->log(
                module: 'ppa-verification-sheet',
                action: 'auto_generate_checklist',
                actor: $actor,
                transaction: $transaction,
                referenceType: PpaVerificationSheet::class,
                referenceId: $sheet->id,
                newValue: ['status' => PpaVerificationSheetStatus::SUBMITTED->value],
            );

            return $sheet->fresh('items.documentType');
        });
    }

    public function mismatchSummary(Transaction $transaction): array
    {
        $sheet = $transaction->ppaVerificationSheet()->with('items.documentType')->first();

        if (! $sheet) {
            return [];
        }

        return $sheet->items->map(function (PpaVerificationSheetItem $item) use ($transaction) {
            $actualAvailable = $this->hasRegisteredOrUploadedDocument($transaction, $item->documentType);

            return [
                'document_name' => $item->documentType?->name,
                'checklist_status' => $item->attachment_status?->value,
                'actual_available' => $actualAvailable,
                'is_mismatch' => ($item->attachment_status === AttachmentStatus::ATTACHED) !== $actualAvailable,
                'notes' => $item->notes,
            ];
        })->all();
    }

    protected function hasRegisteredOrUploadedDocument(Transaction $transaction, ?DocumentType $documentType): bool
    {
        if (! $documentType) {
            return false;
        }

        if ($transaction->latestDocuments()->where('document_type_id', $documentType->id)->exists()) {
            return true;
        }

        return match ($documentType->code) {
            'PPA_MEMO_PERMOHONAN' => (bool) $transaction->memoRequest?->file_path,
            'PPA_PERJANJIAN' => (bool) $transaction->agreementReference?->file_path,
            default => false,
        };
    }
}
