<?php

namespace App\Modules\InvoiceVerification\Policies;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;

class ApprovalTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(
            \App\Modules\InvoiceVerification\Domain\Enums\RoleCode::KEPALA_DEPARTEMEN,
            \App\Modules\InvoiceVerification\Domain\Enums\RoleCode::KEPALA_DIVISI,
            \App\Modules\InvoiceVerification\Domain\Enums\RoleCode::ADMIN_DIVISI,
        ) || $user->pendingApprovals()->exists();
    }

    public function process(User $user, ApprovalTransaction $approvalTransaction): bool
    {
        return $approvalTransaction->approver_user_id === $user->id;
    }
}
