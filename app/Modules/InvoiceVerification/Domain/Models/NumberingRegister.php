<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberingRegister extends InvoiceVerificationModel
{
    protected $table = 'numbering_register';

    protected $casts = [
        'received_date' => 'date',
        'invoice_date' => 'date',
        'generated_at' => 'datetime',
        'contract_value' => 'decimal:2',
        'invoice_value' => 'decimal:2',
        'ppn_value' => 'decimal:2',
        'pph_value' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
