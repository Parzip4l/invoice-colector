<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends InvoiceVerificationModel
{
    public const UPDATED_AT = null;

    protected $casts = [
        'old_value_json' => 'array',
        'new_value_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
