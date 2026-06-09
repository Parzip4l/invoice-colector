<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompiledDocument extends InvoiceVerificationModel
{
    protected $casts = [
        'compiled_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function compiler(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'compiled_by');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'archived_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompiledDocumentItem::class);
    }
}
