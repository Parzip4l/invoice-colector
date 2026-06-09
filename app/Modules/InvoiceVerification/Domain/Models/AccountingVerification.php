<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use App\Modules\InvoiceVerification\Domain\Enums\AccountingVerificationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingVerification extends InvoiceVerificationModel
{
    protected function casts(): array
    {
        return [
            'status' => AccountingVerificationStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'verifier_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AccountingVerificationItem::class);
    }
}
