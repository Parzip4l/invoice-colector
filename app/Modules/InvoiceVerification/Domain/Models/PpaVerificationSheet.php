<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use App\Modules\InvoiceVerification\Domain\Enums\PpaVerificationSheetStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpaVerificationSheet extends InvoiceVerificationModel
{
    protected function casts(): array
    {
        return [
            'status' => PpaVerificationSheetStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function filledBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'filled_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PpaVerificationSheetItem::class, 'verification_sheet_id');
    }
}
