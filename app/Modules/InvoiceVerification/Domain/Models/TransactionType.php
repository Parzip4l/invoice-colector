<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use App\Modules\InvoiceVerification\Domain\Enums\UploadScheme;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionType extends InvoiceVerificationModel
{
    protected function casts(): array
    {
        return [
            'code' => TransactionTypeCode::class,
            'upload_scheme' => UploadScheme::class,
        ];
    }

    public function documentTypes(): HasMany
    {
        return $this->hasMany(DocumentType::class);
    }

    public function approvalFlows(): HasMany
    {
        return $this->hasMany(ApprovalFlow::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(TemplateReference::class);
    }
}
