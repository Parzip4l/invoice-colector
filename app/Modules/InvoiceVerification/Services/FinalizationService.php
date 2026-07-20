<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Models\CompiledDocument;
use App\Modules\InvoiceVerification\Domain\Models\CompiledDocumentItem;
use App\Modules\InvoiceVerification\Domain\Models\NumberingRegister;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Services\Contracts\DocumentCompiler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FinalizationService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected RegistrationNumberService $registrationNumberService,
        protected DocumentCompiler $documentCompiler,
        protected TransactionLifecycleService $transactionLifecycleService,
    ) {
    }

    public function finalize(Transaction $transaction, User $actor): void
    {
        $transaction->loadMissing('invoiceMetadata', 'vendor', 'latestDocuments.documentType', 'generatedDocuments', 'ppaVerificationSheet', 'memoRequest', 'agreementReference');

        if (! $transaction->invoiceMetadata) {
            throw ValidationException::withMessages([
                'invoice_metadata' => 'Invoice metadata wajib diisi sebelum finalisasi.',
            ]);
        }

        DB::transaction(function () use ($transaction, $actor) {
            $register = NumberingRegister::updateOrCreate(
                ['transaction_id' => $transaction->id],
                [
                    'register_number' => $this->registrationNumberService->generateRegisterNumber(),
                    'vendor_name' => $transaction->vendor?->name ?? '-',
                    'received_date' => $transaction->invoiceMetadata->received_date ?? now()->toDateString(),
                    'invoice_number' => $transaction->invoiceMetadata->invoice_number,
                    'invoice_date' => $transaction->invoiceMetadata->invoice_date,
                    'account_number' => $transaction->invoiceMetadata->account_number,
                    'account_name' => $transaction->invoiceMetadata->account_name,
                    'bank_name' => $transaction->invoiceMetadata->bank_name,
                    'memo_number' => $transaction->invoiceMetadata->memo_number,
                    'contract_number' => $transaction->invoiceMetadata->contract_number,
                    'contract_value' => $transaction->invoiceMetadata->contract_value,
                    'invoice_value' => $transaction->invoiceMetadata->invoice_value,
                    'ppn_value' => $transaction->invoiceMetadata->ppn_value,
                    'pph_value' => $transaction->invoiceMetadata->pph_value,
                    'description' => $transaction->invoiceMetadata->description,
                    'generated_at' => now(),
                ],
            );

            $orderedDocuments = $this->collectCompiledDocuments($transaction);
            $compiledPayload = $this->documentCompiler->compile(
                $transaction,
                $orderedDocuments,
                'transactions/'.$transaction->id.'/compiled',
            );

            $compiledDocument = CompiledDocument::updateOrCreate(
                ['transaction_id' => $transaction->id],
                [
                    ...$compiledPayload,
                    'compiled_at' => now(),
                    'compiled_by' => $actor->id,
                ],
            );

            CompiledDocumentItem::query()
                ->where('compiled_document_id', $compiledDocument->id)
                ->delete();

            foreach ($orderedDocuments as $index => $document) {
                CompiledDocumentItem::create([
                    'compiled_document_id' => $compiledDocument->id,
                    'source_type' => $document['source_type'],
                    'source_id' => $document['source_id'],
                    'included_as' => $document['label'],
                    'sort_order' => $index + 1,
                ]);
            }

            if (! empty($compiledDocument->compiled_file_path)) {
                $archiveDisk = config('invoice_verification.storage.archive_disk', $compiledDocument->compiled_file_disk);
                $archivePath = 'archive/'.$transaction->id.'/'.$compiledDocument->compiled_file_name;
                $stream = Storage::disk($compiledDocument->compiled_file_disk)->readStream($compiledDocument->compiled_file_path);

                if (is_resource($stream)) {
                    Storage::disk($archiveDisk)->writeStream($archivePath, $stream);
                    fclose($stream);
                }

                $compiledDocument->update([
                    'archive_disk' => $archiveDisk,
                    'archive_path' => $archivePath,
                ]);
            }

            $this->transactionLifecycleService->transition(
                $transaction,
                TransactionStatus::COMPLETED,
                TransactionStep::FINALIZATION,
                $actor,
                'Akuntansi menyetujui verifikasi. Dokumen final telah dicompile dan data masuk ke penomoran.',
            );

            $this->auditLogService->log(
                module: 'finalization',
                action: 'numbering_register_generation',
                actor: $actor,
                transaction: $transaction,
                referenceType: NumberingRegister::class,
                referenceId: $register->id,
                newValue: ['register_number' => $register->register_number],
            );

            $this->auditLogService->log(
                module: 'finalization',
                action: 'compile_final_document',
                actor: $actor,
                transaction: $transaction,
                referenceType: CompiledDocument::class,
                referenceId: $compiledDocument->id,
                newValue: [
                    'compiled_file_name' => $compiledDocument->compiled_file_name,
                    'total_files' => $compiledDocument->total_files,
                ],
            );

        });
    }

    public function completeFinanceProcessing(Transaction $transaction, User $actor, ?string $notes = null): void
    {
        $transaction->loadMissing('compiledDocument');

        DB::transaction(function () use ($transaction, $actor, $notes) {
            $compiledDocument = $transaction->compiledDocument;

            if ($compiledDocument) {
                $compiledDocument->update([
                    'archived_at' => now(),
                    'archived_by_user_id' => $actor->id,
                ]);
            }

            $this->auditLogService->log(
                module: 'finance',
                action: 'complete_finance_process',
                actor: $actor,
                transaction: $transaction,
                referenceType: CompiledDocument::class,
                referenceId: $compiledDocument?->id,
                newValue: [
                    'archive_path' => $compiledDocument?->archive_path,
                    'notes' => $notes,
                ],
            );

            $this->transactionLifecycleService->transition(
                $transaction,
                TransactionStatus::ARCHIVED,
                TransactionStep::ARCHIVE,
                $actor,
                $notes ?: 'Finance telah menyelesaikan register, numbering, dan arsip final.',
            );
        });
    }

    protected function collectCompiledDocuments(Transaction $transaction): array
    {
        $order = config('invoice_verification.document_compile_order.'.$transaction->transactionType->code->value, []);
        $documents = [];

        foreach ($order as $documentCode) {
            $generated = $transaction->generatedDocuments->firstWhere('document_code', $documentCode);

            if ($generated) {
                $documents[] = [
                    'source_type' => 'generated_document',
                    'source_id' => $generated->id,
                    'label' => $generated->document_code,
                    'path' => $generated->file_path,
                    'disk' => $generated->file_disk,
                    'file_name' => $generated->file_name,
                    'extension' => pathinfo((string) $generated->file_name, PATHINFO_EXTENSION),
                ];

                continue;
            }

            if ($documentCode === 'PPA_LEMBAR_VERIFIKASI' && $transaction->ppaVerificationSheet) {
                $documents[] = [
                    'source_type' => 'ppa_verification_sheet',
                    'source_id' => $transaction->ppaVerificationSheet->id,
                    'label' => 'PPA Verification Sheet',
                    'path' => $transaction->ppaVerificationSheet->file_path,
                    'disk' => $transaction->ppaVerificationSheet->file_disk,
                    'file_name' => $transaction->ppaVerificationSheet->file_name,
                    'extension' => pathinfo((string) $transaction->ppaVerificationSheet->file_name, PATHINFO_EXTENSION),
                ];

                continue;
            }

            if ($documentCode === 'PPA_MEMO_PERMOHONAN' && $transaction->memoRequest?->file_path) {
                $documents[] = [
                    'source_type' => 'memo_request',
                    'source_id' => $transaction->memoRequest->id,
                    'label' => $transaction->memoRequest->memo_number,
                    'path' => $transaction->memoRequest->file_path,
                    'disk' => $transaction->memoRequest->file_disk,
                    'file_name' => $transaction->memoRequest->file_name,
                    'extension' => $transaction->memoRequest->file_extension,
                ];

                continue;
            }

            if ($documentCode === 'PPA_PERJANJIAN' && $transaction->agreementReference?->file_path) {
                $documents[] = [
                    'source_type' => 'agreement_reference',
                    'source_id' => $transaction->agreementReference->id,
                    'label' => $transaction->agreementReference->contract_number,
                    'path' => $transaction->agreementReference->file_path,
                    'disk' => $transaction->agreementReference->file_disk,
                    'file_name' => $transaction->agreementReference->file_name,
                    'extension' => $transaction->agreementReference->file_extension,
                ];

                continue;
            }

            $document = $transaction->latestDocuments->firstWhere('documentType.code', $documentCode);

            if ($document) {
                $documents[] = [
                    'source_type' => 'transaction_document',
                    'source_id' => $document->id,
                    'label' => $document->documentType->name,
                    'path' => $document->file_path,
                    'disk' => $document->file_disk,
                    'file_name' => $document->file_name,
                    'extension' => $document->file_extension,
                ];
            }
        }

        if ($transaction->usesCombinedUpload()) {
            foreach ($transaction->latestDocuments as $document) {
                $documents[] = [
                    'source_type' => 'transaction_document',
                    'source_id' => $document->id,
                    'label' => $document->document_label ?: $document->documentType->name,
                    'path' => $document->file_path,
                    'disk' => $document->file_disk,
                    'file_name' => $document->file_name,
                    'extension' => $document->file_extension,
                ];
            }
        }

        return array_values(array_unique($documents, SORT_REGULAR));
    }
}
