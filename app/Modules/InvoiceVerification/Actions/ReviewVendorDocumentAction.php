<?php

namespace App\Modules\InvoiceVerification\Actions;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\VendorDocumentReviewStatus;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;
use App\Modules\InvoiceVerification\Services\VendorDocumentReviewService;

class ReviewVendorDocumentAction
{
    public function __construct(
        protected VendorDocumentReviewService $vendorDocumentReviewService,
    ) {
    }

    public function execute(TransactionDocument $document, User $actor, VendorDocumentReviewStatus $status, ?string $notes = null)
    {
        return $this->vendorDocumentReviewService->review($document, $actor, $status, $notes);
    }
}
