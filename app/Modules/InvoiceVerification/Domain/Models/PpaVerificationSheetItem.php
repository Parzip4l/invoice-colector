<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use App\Modules\InvoiceVerification\Domain\Enums\AttachmentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpaVerificationSheetItem extends InvoiceVerificationModel
{
    protected function casts(): array
    {
        return [
            'attachment_status' => AttachmentStatus::class,
        ];
    }

    public function verificationSheet(): BelongsTo
    {
        return $this->belongsTo(PpaVerificationSheet::class, 'verification_sheet_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
