<?php

namespace App\Modules\InvoiceVerification\Domain\Models;

use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends InvoiceVerificationModel
{
    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'current_step' => TransactionStep::class,
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'contract_value' => 'decimal:2',
        ];
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function memoRequest(): BelongsTo
    {
        return $this->belongsTo(MemoRequest::class);
    }

    public function agreementReference(): BelongsTo
    {
        return $this->belongsTo(AgreementReference::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function parties(): HasMany
    {
        return $this->hasMany(TransactionParty::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TransactionDocument::class);
    }

    public function latestDocuments(): HasMany
    {
        return $this->documents()->where('is_latest', true);
    }

    public function approvalTransactions(): HasMany
    {
        return $this->hasMany(ApprovalTransaction::class);
    }

    public function accountingVerification(): HasOne
    {
        return $this->hasOne(AccountingVerification::class);
    }

    public function invoiceMetadata(): HasOne
    {
        return $this->hasOne(InvoiceMetadata::class);
    }

    public function numberingRegister(): HasOne
    {
        return $this->hasOne(NumberingRegister::class);
    }

    public function compiledDocument(): HasOne
    {
        return $this->hasOne(CompiledDocument::class);
    }

    public function ppaVerificationSheet(): HasOne
    {
        return $this->hasOne(PpaVerificationSheet::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TransactionStatusHistory::class)->latest('created_at');
    }

    public function isPpa(): bool
    {
        return $this->transactionType?->code === TransactionTypeCode::PPA;
    }

    public function usesCombinedUpload(): bool
    {
        return ! $this->isPpa();
    }
}
