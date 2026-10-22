<?php

namespace App\Modules\InvoiceVerification\Observers;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Services\PortalApprovalResolverService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ApprovalTransactionObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(ApprovalTransaction $approval): void
    {
        if (! $approval->wasChanged('status') || $approval->status === ApprovalStatus::PENDING) {
            return;
        }

        app(PortalApprovalResolverService::class)->notifyApprovalDecided(
            $approval->fresh(['transaction.owner', 'approvalFlow', 'approver']),
            auth()->user() instanceof User ? auth()->user() : null,
        );
    }
}
