<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use App\Modules\InvoiceVerification\Domain\Enums\VendorDocumentReviewStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorDocumentReview extends InvoiceVerificationModel
{
    protected function casts(): array
    {
        return [
            'status' => VendorDocumentReviewStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function transactionDocument(): BelongsTo
    {
        return $this->belongsTo(TransactionDocument::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }
}
