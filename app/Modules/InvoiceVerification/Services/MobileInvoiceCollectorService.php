<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionStatusHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class MobileInvoiceCollectorService
{
    public function accessibleQuery(User $user): Builder
    {
        $query = Transaction::query();

        if ($user->hasRole(RoleCode::ADMIN_DIVISI, RoleCode::AKUNTANSI, RoleCode::FINANCE)) {
            return $query;
        }

        if ($user->hasRole(RoleCode::VENDOR)) {
            $vendorId = $user->linkedVendor()?->id;

            return $query->where(function (Builder $builder) use ($user, $vendorId) {
                $builder->where('owner_user_id', $user->id);
                if ($vendorId) {
                    $builder->orWhere('vendor_id', $vendorId);
                }
            });
        }

        if ($user->hasRole(RoleCode::KEPALA_DEPARTEMEN)) {
            return $query->where(function (Builder $builder) use ($user) {
                $builder->where('department_id', $user->department_id)
                    ->orWhereHas('approvalTransactions', fn (Builder $approval) => $approval->where('approver_user_id', $user->id));
            });
        }

        if ($user->hasRole(RoleCode::KEPALA_DIVISI)) {
            return $query->where(function (Builder $builder) use ($user) {
                $builder->where('division_id', $user->division_id)
                    ->orWhereHas('approvalTransactions', fn (Builder $approval) => $approval->where('approver_user_id', $user->id));
            });
        }

        return $query->where('owner_user_id', $user->id);
    }

    public function canAccess(User $user, Transaction $transaction): bool
    {
        return $this->accessibleQuery($user)->whereKey($transaction->getKey())->exists();
    }

    public function serializeSummary(Transaction $transaction): array
    {
        $transaction->loadMissing(['transactionType', 'vendor', 'owner', 'invoiceMetadata']);
        $metadata = $transaction->invoiceMetadata;

        return [
            'id' => (string) $transaction->id,
            'registration_number' => $transaction->registration_number,
            'title' => $transaction->title,
            'status' => $this->enumSummary($transaction->status),
            'current_step' => $this->enumSummary($transaction->current_step),
            'transaction_type' => $transaction->transactionType ? [
                'id' => (string) $transaction->transactionType->id,
                'code' => $this->enumValue($transaction->transactionType->code),
                'name' => $transaction->transactionType->name,
            ] : null,
            'vendor' => $transaction->vendor ? [
                'id' => (string) $transaction->vendor->id,
                'name' => $transaction->vendor->name,
            ] : null,
            'owner' => $this->userSummary($transaction->owner),
            'invoice' => $metadata ? [
                'number' => $metadata->invoice_number,
                'date' => optional($metadata->invoice_date)->toDateString(),
                'invoice_value' => (float) ($metadata->invoice_value ?? 0),
                'ppn_value' => (float) ($metadata->ppn_value ?? 0),
                'total_amount' => (float) (($metadata->invoice_value ?? 0) + ($metadata->ppn_value ?? 0)),
            ] : null,
            'submitted_at' => optional($transaction->submitted_at)->toIso8601String(),
            'created_at' => optional($transaction->created_at)->toIso8601String(),
            'updated_at' => optional($transaction->updated_at)->toIso8601String(),
        ];
    }

    public function serialize(Transaction $transaction): array
    {
        $transaction->loadMissing([
            'transactionType',
            'vendor',
            'division',
            'department',
            'creator',
            'owner',
            'invoiceMetadata',
            'latestDocuments.documentType',
            'latestDocuments.uploadedByUser',
            'latestDocuments.uploadedByVendor',
            'approvalTransactions.approvalFlow',
            'approvalTransactions.approver',
            'statusHistory.changer',
            'accountingVerification',
            'numberingRegister',
        ]);

        $metadata = $transaction->invoiceMetadata;

        return [
            'id' => (string) $transaction->id,
            'registration_number' => $transaction->registration_number,
            'title' => $transaction->title,
            'description' => $transaction->description,
            'status' => $this->enumSummary($transaction->status),
            'current_step' => $this->enumSummary($transaction->current_step),
            'transaction_type' => $transaction->transactionType ? [
                'id' => (string) $transaction->transactionType->id,
                'code' => $this->enumValue($transaction->transactionType->code),
                'name' => $transaction->transactionType->name,
            ] : null,
            'vendor' => $transaction->vendor ? [
                'id' => (string) $transaction->vendor->id,
                'name' => $transaction->vendor->name,
            ] : null,
            'owner' => $this->userSummary($transaction->owner),
            'creator' => $this->userSummary($transaction->creator),
            'division' => $transaction->division ? ['id' => (string) $transaction->division->id, 'name' => $transaction->division->name] : null,
            'department' => $transaction->department ? ['id' => (string) $transaction->department->id, 'name' => $transaction->department->name] : null,
            'invoice' => $metadata ? [
                'number' => $metadata->invoice_number,
                'date' => optional($metadata->invoice_date)->toDateString(),
                'received_date' => optional($metadata->received_date)->toDateString(),
                'contract_number' => $metadata->contract_number,
                'contract_value' => (float) ($metadata->contract_value ?? 0),
                'invoice_value' => (float) ($metadata->invoice_value ?? 0),
                'ppn_value' => (float) ($metadata->ppn_value ?? 0),
                'pph_value' => (float) ($metadata->pph_value ?? 0),
                'total_amount' => (float) (($metadata->invoice_value ?? 0) + ($metadata->ppn_value ?? 0)),
                'bank_name' => $metadata->bank_name,
                'account_name' => $metadata->account_name,
                'account_number' => $metadata->account_number,
            ] : null,
            'submitted_at' => optional($transaction->submitted_at)->toIso8601String(),
            'scheduled_payment_at' => optional($transaction->scheduled_payment_at)->toIso8601String(),
            'paid_at' => optional($transaction->paid_at)->toIso8601String(),
            'completed_at' => optional($transaction->completed_at)->toIso8601String(),
            'created_at' => optional($transaction->created_at)->toIso8601String(),
            'updated_at' => optional($transaction->updated_at)->toIso8601String(),
            'documents' => $transaction->latestDocuments->map(fn ($document) => [
                'id' => (string) $document->id,
                'name' => $document->document_label ?: $document->documentType?->name ?: $document->file_name,
                'file_name' => $document->file_name,
                'mime_type' => $document->mime_type,
                'size' => (int) $document->file_size,
                'status' => $this->enumValue($document->status),
                'uploaded_at' => optional($document->uploaded_at)->toIso8601String(),
                'url' => route('api.mobile.invoice-collector.documents.show', [$transaction, $document]),
            ])->values(),
            'history' => $this->history($transaction),
            'approvals' => $transaction->approvalTransactions
                ->sortBy(fn ($approval) => (int) $approval->approvalFlow?->step_no)
                ->map(fn ($approval) => [
                    'id' => (string) $approval->id,
                    'status' => strtolower($this->enumValue($approval->status)),
                    'step' => [
                        'number' => (int) $approval->approvalFlow?->step_no,
                        'code' => $approval->approvalFlow?->step_code,
                        'name' => $approval->approvalFlow?->step_name,
                    ],
                    'approver' => $this->userSummary($approval->approver),
                    'notes' => $approval->notes,
                    'acted_at' => optional($approval->action_at)->toIso8601String(),
                ])->values(),
        ];
    }

    public function history(Transaction $transaction): array
    {
        $transaction->loadMissing('statusHistory.changer');

        return $transaction->statusHistory
            ->sortByDesc('created_at')
            ->map(fn (TransactionStatusHistory $history) => [
                'id' => (string) $history->id,
                'action' => 'Status berubah menjadi '.$this->label($history->to_status),
                'from_status' => $history->from_status ? $this->label($history->from_status) : null,
                'to_status' => $this->label($history->to_status),
                'from_step' => $history->from_step ? $this->label($history->from_step) : null,
                'to_step' => $this->label($history->to_step),
                'notes' => $history->notes,
                'actor' => $this->userSummary($history->changer),
                'created_at' => optional($history->created_at)->toIso8601String(),
            ])->values()->all();
    }

    private function enumSummary(mixed $value): array
    {
        return [
            'code' => $this->enumValue($value),
            'name' => method_exists($value, 'label') ? $value->label() : $this->label($this->enumValue($value)),
        ];
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
    }

    private function label(string $value): string
    {
        return Str::of($value)->replace(['_', '-'], ' ')->lower()->title()->toString();
    }

    private function userSummary(?User $user): ?array
    {
        return $user ? ['id' => $user->id, 'name' => $user->name, 'email' => $user->email] : null;
    }
}
