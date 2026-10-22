<?php

namespace App\Modules\InvoiceVerification\Observers;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Services\PortalApprovalResolverService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class TransactionObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(Transaction $transaction): void
    {
        if (! $transaction->wasChanged(['status', 'current_step'])) {
            return;
        }

        app(PortalApprovalResolverService::class)->notifyTransactionStatusChanged(
            $transaction->fresh('owner'),
            $transaction->getRawOriginal('status'),
            auth()->user() instanceof User ? auth()->user() : null,
        );
    }
}
