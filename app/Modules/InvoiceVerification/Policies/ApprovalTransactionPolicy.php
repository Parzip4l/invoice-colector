<?php

namespace App\Modules\InvoiceVerification\Policies;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;

class ApprovalTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->pendingApprovals()->exists();
    }

    public function process(User $user, ApprovalTransaction $approvalTransaction): bool
    {
        return $approvalTransaction->approver_user_id === $user->id;
    }
}
