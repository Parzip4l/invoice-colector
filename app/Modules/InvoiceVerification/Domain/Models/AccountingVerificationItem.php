<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use App\Modules\InvoiceVerification\Domain\Enums\AccountingVerificationItemStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingVerificationItem extends InvoiceVerificationModel
{
    protected function casts(): array
    {
        return [
            'status' => AccountingVerificationItemStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function accountingVerification(): BelongsTo
    {
        return $this->belongsTo(AccountingVerification::class);
    }

    public function transactionDocument(): BelongsTo
    {
        return $this->belongsTo(TransactionDocument::class);
    }
}
