<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionStatusHistory;

class TransactionLifecycleService
{
    public function transition(
        Transaction $transaction,
        TransactionStatus $status,
        TransactionStep $step,
        ?User $actor = null,
        ?string $notes = null,
    ): Transaction {
        $originalStatus = $transaction->status;
        $originalStep = $transaction->current_step;

        $transaction->forceFill([
            'status' => $status,
            'current_step' => $step,
            'completed_at' => in_array($status, [TransactionStatus::COMPLETED, TransactionStatus::ARCHIVED], true)
                ? now()
                : $transaction->completed_at,
        ])->save();

        TransactionStatusHistory::create([
            'transaction_id' => $transaction->id,
            'from_status' => $originalStatus?->value,
            'to_status' => $status->value,
            'from_step' => $originalStep?->value,
            'to_step' => $step->value,
            'changed_by' => $actor?->id,
            'notes' => $notes,
        ]);

        return $transaction->refresh();
    }
}
