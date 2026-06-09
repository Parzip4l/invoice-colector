<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use App\Modules\InvoiceVerification\Domain\Enums\ApprovalMode;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceType;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentUploadMode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends InvoiceVerificationModel
{
    protected function casts(): array
    {
        return [
            'source_type' => DocumentSourceType::class,
            'upload_mode' => DocumentUploadMode::class,
            'approval_mode' => ApprovalMode::class,
            'is_required' => 'boolean',
        ];
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TransactionDocument::class);
    }

    public function verificationSheetItems(): HasMany
    {
        return $this->hasMany(PpaVerificationSheetItem::class);
    }
}
