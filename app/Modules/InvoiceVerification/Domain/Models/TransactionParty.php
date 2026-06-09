<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionParty extends InvoiceVerificationModel
{
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
