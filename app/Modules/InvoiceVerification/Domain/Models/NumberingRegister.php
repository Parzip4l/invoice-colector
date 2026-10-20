<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberingRegister extends InvoiceVerificationModel
{
    protected $table = 'numbering_register';

    protected $casts = [
        'received_date' => 'date',
        'invoice_date' => 'date',
        'upload_date' => 'date',
        'hardcopy_received' => 'boolean',
        'hardcopy_received_date' => 'date',
        'tax_invoice_date' => 'date',
        'payment_expedition_date' => 'date',
        'payment_date' => 'date',
        'return_date' => 'date',
        'generated_at' => 'datetime',
        'contract_value' => 'decimal:2',
        'invoice_value' => 'decimal:2',
        'ppn_percentage' => 'decimal:4',
        'ppn_value' => 'decimal:2',
        'pph_percentage' => 'decimal:4',
        'pph_base_value' => 'decimal:2',
        'pph_value' => 'decimal:2',
        'discount_deduction_stamp_value' => 'decimal:2',
        'return_amount' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
