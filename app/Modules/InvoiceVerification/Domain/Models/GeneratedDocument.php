<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use App\Modules\InvoiceVerification\Domain\Enums\ApprovalMode;
use App\Modules\InvoiceVerification\Domain\Enums\GeneratedDocumentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneratedDocument extends InvoiceVerificationModel
{
    protected function casts(): array
    {
        return [
            'approval_mode' => ApprovalMode::class,
            'generation_status' => GeneratedDocumentStatus::class,
            'generated_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function templateReference(): BelongsTo
    {
        return $this->belongsTo(TemplateReference::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'generated_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ApprovalTransaction::class);
    }
}
