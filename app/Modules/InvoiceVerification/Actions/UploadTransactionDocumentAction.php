<?php

namespace App\Modules\InvoiceVerification\Actions;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceActor;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use App\Modules\InvoiceVerification\Services\DocumentUploadService;
use Illuminate\Http\UploadedFile;

class UploadTransactionDocumentAction
{
    public function __construct(
        protected DocumentUploadService $documentUploadService,
    ) {
    }

    public function execute(
        Transaction $transaction,
        DocumentType $documentType,
        UploadedFile $file,
        DocumentSourceActor $sourceActor,
        ?User $user = null,
        ?Vendor $vendor = null,
        ?string $documentLabel = null,
        ?array $documentInformation = null,
    ) {
        return $this->documentUploadService->upload(
            transaction: $transaction,
            documentType: $documentType,
            file: $file,
            sourceActor: $sourceActor,
            user: $user,
            vendor: $vendor,
            documentLabel: $documentLabel,
            documentInformation: $documentInformation,
        );
    }
}
