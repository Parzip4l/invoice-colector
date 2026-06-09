<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalTransaction extends InvoiceVerificationModel
{
    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'action_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function generatedDocument(): BelongsTo
    {
        return $this->belongsTo(GeneratedDocument::class);
    }

    public function approvalFlow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approver_user_id');
    }
}
