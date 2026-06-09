<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceActor;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionDocumentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransactionDocument extends InvoiceVerificationModel
{
    protected function casts(): array
    {
        return [
            'source_actor' => DocumentSourceActor::class,
            'status' => TransactionDocumentStatus::class,
            'document_information_json' => 'array',
            'is_latest' => 'boolean',
            'uploaded_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by_user_id');
    }

    public function uploadedByVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'uploaded_by_vendor_id');
    }

    public function vendorReview(): HasOne
    {
        return $this->hasOne(VendorDocumentReview::class);
    }

    public function accountingVerificationItems(): HasMany
    {
        return $this->hasMany(AccountingVerificationItem::class);
    }
}
