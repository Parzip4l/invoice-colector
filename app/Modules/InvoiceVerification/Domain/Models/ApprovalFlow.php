<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalFlow extends InvoiceVerificationModel
{
    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function approvalTransactions(): HasMany
    {
        return $this->hasMany(ApprovalTransaction::class);
    }
}
