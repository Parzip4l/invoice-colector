<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends InvoiceVerificationModel
{
    protected $casts = [
        'is_active' => 'boolean',
        'petty_cash_ceiling' => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
