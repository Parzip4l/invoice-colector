<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Models\CompiledDocument;
use App\Modules\InvoiceVerification\Domain\Models\CompiledDocumentItem;
use App\Modules\InvoiceVerification\Domain\Models\NumberingRegister;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinalizationService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected RegistrationNumberService $registrationNumberService,
        protected TransactionLifecycleService $transactionLifecycleService,
    ) {
    }

    public function finalize(Transaction $transaction, User $actor): void
    {
        $transaction->loadMissing('invoiceMetadata', 'vendor', 'latestDocuments.documentType', 'generatedDocuments', 'ppaVerificationSheet', 'memoRequest', 'agreementReference', 'division', 'department.users');

        if (! $transaction->invoiceMetadata) {
            throw ValidationException::withMessages([
                'invoice_metadata' => 'Invoice metadata wajib diisi sebelum finalisasi.',
            ]);
        }

        DB::transaction(function () use ($transaction, $actor) {
            $register = NumberingRegister::firstOrNew(['transaction_id' => $transaction->id]);

            if (! $register->exists) {
                $register->register_number = $this->registrationNumberService->generateRegisterNumber($transaction);
            }

            $taxDocument = $transaction->latestDocuments
                ->first(fn ($document) => in_array($document->documentType?->code?->value ?? $document->documentType?->code, ['PPA_FAKTUR_PAJAK', 'PNK_FAKTUR_PAJAK'], true));
            $firstUploadedAt = $transaction->latestDocuments
                ->pluck('uploaded_at')
                ->filter()
                ->sort()
                ->first();
            $departmentHeadName = $transaction->department?->users
                ?->first(fn ($user) => ($user->role_code?->value ?? $user->role_code) === RoleCode::KEPALA_DEPARTEMEN->value)
                ?->name;

            $register->fill([
                'vendor_name' => $transaction->vendor?->name ?? $transaction->division?->name ?? '-',
                'received_date' => $transaction->invoiceMetadata->received_date ?? now()->toDateString(),
                'invoice_number' => $transaction->invoiceMetadata->invoice_number,
                'invoice_date' => $transaction->invoiceMetadata->invoice_date,
                'upload_date' => $register->upload_date ?? $firstUploadedAt?->toDateString(),
                'account_number' => $transaction->invoiceMetadata->account_number,
                'account_name' => $transaction->invoiceMetadata->account_name,
                'bank_name' => $transaction->invoiceMetadata->bank_name,
                'memo_number' => $transaction->invoiceMetadata->memo_number,
                'contract_number' => $transaction->invoiceMetadata->contract_number,
                'contract_value' => $transaction->invoiceMetadata->contract_value,
                'invoice_value' => $transaction->invoiceMetadata->invoice_value,
                'ppn_percentage' => $transaction->invoiceMetadata->ppn_percentage,
                'ppn_value' => $transaction->invoiceMetadata->ppn_value,
                'pph_percentage' => $transaction->invoiceMetadata->pph_percentage,
                'pph_base_value' => $transaction->invoiceMetadata->pph_base_value,
                'pph_value' => $transaction->invoiceMetadata->pph_value,
                'tax_invoice_number' => $register->tax_invoice_number ?? data_get($taxDocument?->document_information_json, 'document_number'),
                'tax_invoice_date' => $register->tax_invoice_date ?? data_get($taxDocument?->document_information_json, 'document_date'),
                'payment_expedition_date' => $register->payment_expedition_date ?? $transaction->scheduled_payment_at?->toDateString(),
                'payment_date' => $register->payment_date ?? $transaction->paid_at?->toDateString(),
                'description' => $transaction->invoiceMetadata->description,
                'department_head_name' => $register->department_head_name ?? $departmentHeadName,
                'division_name' => $register->division_name ?? $transaction->division?->name,
                'generated_at' => $register->generated_at ?? now(),
            ]);
            $register->save();

            $compiledDocument = $this->registerCompilationManifest($transaction, $actor);

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
                    'storage_mode' => 'on_demand',
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
                    'storage_mode' => 'on_demand',
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

    public function collectCompiledDocuments(Transaction $transaction): array
    {
        $transaction->loadMissing([
            'transactionType',
            'generatedDocuments',
            'latestDocuments.documentType',
            'ppaVerificationSheet',
            'memoRequest',
            'agreementReference',
        ]);

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

        // Append every available source not explicitly listed in the configured order.
        // This keeps the bundle complete for transaction types with a combined upload
        // scheme and for newly introduced generated documents.
        foreach ($transaction->generatedDocuments as $generated) {
            if (! $generated->file_path) {
                continue;
            }

            $documents[] = [
                'source_type' => 'generated_document',
                'source_id' => $generated->id,
                'label' => $generated->document_code,
                'path' => $generated->file_path,
                'disk' => $generated->file_disk,
                'file_name' => $generated->file_name,
                'extension' => pathinfo((string) $generated->file_name, PATHINFO_EXTENSION),
            ];
        }

        if ($transaction->ppaVerificationSheet?->file_path) {
            $documents[] = [
                'source_type' => 'ppa_verification_sheet',
                'source_id' => $transaction->ppaVerificationSheet->id,
                'label' => 'PPA Verification Sheet',
                'path' => $transaction->ppaVerificationSheet->file_path,
                'disk' => $transaction->ppaVerificationSheet->file_disk,
                'file_name' => $transaction->ppaVerificationSheet->file_name,
                'extension' => pathinfo((string) $transaction->ppaVerificationSheet->file_name, PATHINFO_EXTENSION),
            ];
        }

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

        if ($transaction->memoRequest?->file_path) {
            $documents[] = [
                'source_type' => 'memo_request',
                'source_id' => $transaction->memoRequest->id,
                'label' => $transaction->memoRequest->memo_number,
                'path' => $transaction->memoRequest->file_path,
                'disk' => $transaction->memoRequest->file_disk,
                'file_name' => $transaction->memoRequest->file_name,
                'extension' => $transaction->memoRequest->file_extension,
            ];
        }

        if ($transaction->agreementReference?->file_path) {
            $documents[] = [
                'source_type' => 'agreement_reference',
                'source_id' => $transaction->agreementReference->id,
                'label' => $transaction->agreementReference->contract_number,
                'path' => $transaction->agreementReference->file_path,
                'disk' => $transaction->agreementReference->file_disk,
                'file_name' => $transaction->agreementReference->file_name,
                'extension' => $transaction->agreementReference->file_extension,
            ];
        }

        if ($transaction->payment_proof_file_path) {
            $documents[] = [
                'source_type' => 'payment_proof',
                'source_id' => $transaction->id,
                'label' => 'Bukti Pembayaran',
                'path' => $transaction->payment_proof_file_path,
                'disk' => $transaction->payment_proof_file_disk,
                'file_name' => $transaction->payment_proof_file_name,
                'extension' => pathinfo((string) $transaction->payment_proof_file_name, PATHINFO_EXTENSION),
            ];
        }

        $uniqueDocuments = [];

        foreach ($documents as $document) {
            $key = $document['source_type'].':'.$document['source_id'];
            $uniqueDocuments[$key] ??= $document;
        }

        return array_values($uniqueDocuments);
    }

    public function registerCompilationManifest(Transaction $transaction, User $actor): CompiledDocument
    {
        $orderedDocuments = $this->collectCompiledDocuments($transaction);

        return DB::transaction(function () use ($transaction, $actor, $orderedDocuments): CompiledDocument {
            $existingCompiledDocument = CompiledDocument::query()
                ->where('transaction_id', $transaction->id)
                ->first();

            if ($existingCompiledDocument) {
                $this->deletePersistedCompilation($existingCompiledDocument);
            }

            $compiledDocument = CompiledDocument::updateOrCreate(
                ['transaction_id' => $transaction->id],
                [
                    'compiled_file_name' => sprintf('kompilasi-%s.pdf', Str::slug($transaction->registration_number)),
                    'compiled_file_disk' => null,
                    'compiled_file_path' => null,
                    'total_files' => count($orderedDocuments),
                    'compiled_at' => now(),
                    'compiled_by' => $actor->id,
                    'archive_disk' => null,
                    'archive_path' => null,
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

            return $compiledDocument->fresh('items');
        });
    }

    protected function deletePersistedCompilation(CompiledDocument $compiledDocument): void
    {
        foreach ([
            [$compiledDocument->compiled_file_disk, $compiledDocument->compiled_file_path],
            [$compiledDocument->archive_disk, $compiledDocument->archive_path],
        ] as [$disk, $path]) {
            if (filled($disk) && filled($path) && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }
}
