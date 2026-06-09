<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceMetadata extends InvoiceVerificationModel
{
    protected $table = 'invoice_metadata';

    protected $casts = [
        'invoice_date' => 'date',
        'contract_value' => 'decimal:2',
        'invoice_value' => 'decimal:2',
        'ppn_value' => 'decimal:2',
        'pph_value' => 'decimal:2',
        'received_date' => 'date',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
