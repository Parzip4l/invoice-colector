<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends InvoiceVerificationModel
{
    public function defaultBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'default_bank_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function invoiceMetadata(): HasMany
    {
        return $this->hasMany(InvoiceMetadata::class);
    }

    public function agreementReferences(): HasMany
    {
        return $this->hasMany(AgreementReference::class);
    }
}
