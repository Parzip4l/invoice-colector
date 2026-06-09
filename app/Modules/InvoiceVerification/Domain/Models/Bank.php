<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends InvoiceVerificationModel
{
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'default_bank_id');
    }
}
