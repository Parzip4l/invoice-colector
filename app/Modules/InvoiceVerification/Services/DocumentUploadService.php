<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceActor;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionDocumentStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentUploadService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected TransactionLifecycleService $transactionLifecycleService,
    ) {
    }

    public function upload(
        Transaction $transaction,
        DocumentType $documentType,
        UploadedFile $file,
        DocumentSourceActor $sourceActor,
        ?User $user = null,
        ?Vendor $vendor = null,
        ?string $documentLabel = null,
        ?array $documentInformation = null,
    ): TransactionDocument {
        return DB::transaction(function () use ($transaction, $documentType, $file, $sourceActor, $user, $vendor, $documentLabel, $documentInformation) {
            $disk = config('invoice_verification.storage.documents_disk');
            $latest = TransactionDocument::query()
                ->where('transaction_id', $transaction->id)
                ->where('document_type_id', $documentType->id)
                ->where('source_actor', $sourceActor->value)
                ->when($documentLabel, fn ($query) => $query->where('document_label', $documentLabel))
                ->where('is_latest', true)
                ->latest('version')
                ->first();

            if ($latest) {
                $latest->update(['is_latest' => false]);
            }

            $version = ($latest?->version ?? 0) + 1;
            $path = $file->store('transactions/'.$transaction->id.'/documents/'.$documentType->code, $disk);
            $status = $sourceActor === DocumentSourceActor::VENDOR
                ? TransactionDocumentStatus::UNDER_REVIEW
                : TransactionDocumentStatus::UPLOADED;

            $document = TransactionDocument::create([
                'transaction_id' => $transaction->id,
                'document_type_id' => $documentType->id,
                'source_actor' => $sourceActor,
                'uploaded_by_user_id' => $user?->id,
                'uploaded_by_vendor_id' => $vendor?->id,
                'document_label' => $documentLabel,
                'document_information_json' => $documentInformation,
                'file_name' => $file->getClientOriginalName(),
                'file_disk' => $disk,
                'file_path' => $path,
                'file_extension' => $file->getClientOriginalExtension(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'version' => $version,
                'status' => $status,
                'is_latest' => true,
                'uploaded_at' => now(),
            ]);

            $this->auditLogService->log(
                module: 'transaction-documents',
                action: $latest ? 'replace_file' : 'upload_file',
                actor: $user ?? User::query()->findOrFail($transaction->created_by),
                transaction: $transaction,
                referenceType: TransactionDocument::class,
                referenceId: $document->id,
                newValue: [
                    'document_type_id' => $documentType->id,
                    'source_actor' => $sourceActor->value,
                    'version' => $version,
                    'file_name' => $document->file_name,
                    'document_information' => $documentInformation,
                ],
            );

            $nextStep = $sourceActor === DocumentSourceActor::VENDOR
                ? TransactionStep::ADMIN_DOCUMENT_REVIEW
                : TransactionStep::INTERNAL_DOCUMENT_UPLOAD;

            $nextStatus = $sourceActor === DocumentSourceActor::VENDOR
                ? TransactionStatus::ADMIN_REVIEW
                : TransactionStatus::DOCUMENT_COLLECTION;

            $this->transactionLifecycleService->transition(
                $transaction,
                $nextStatus,
                $nextStep,
                $user,
                $sourceActor === DocumentSourceActor::VENDOR
                    ? 'Dokumen vendor diunggah untuk pengecekan hasil pekerjaan.'
                    : 'Softcopy dokumen diunggah melalui aplikasi invoice collector.',
            );

            return $document;
        });
    }

    public function openFileContents(TransactionDocument $document): ?string
    {
        if (! Storage::disk($document->file_disk)->exists($document->file_path)) {
            return null;
        }

        return Storage::disk($document->file_disk)->url($document->file_path);
    }
}
