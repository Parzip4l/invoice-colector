<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompiledDocumentItem extends InvoiceVerificationModel
{
    public function compiledDocument(): BelongsTo
    {
        return $this->belongsTo(CompiledDocument::class);
    }
}
