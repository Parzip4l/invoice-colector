<?php

namespace App\Modules\InvoiceVerification\Policies;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet;

class PpaVerificationSheetPolicy
{
    public function view(User $user, ?PpaVerificationSheet $sheet = null): bool
    {
        return $sheet ? $user->can('view', $sheet->transaction) : true;
    }

    public function update(User $user, mixed $sheet = null): bool
    {
        if (! $sheet instanceof PpaVerificationSheet) {
            return false;
        }

        $transaction = $sheet->transaction;

        return $user->hasRole(RoleCode::ADMIN_DIVISI, RoleCode::USER_DIVISI)
            && $transaction->owner_user_id === $user->id
            && in_array($transaction->status, [TransactionStatus::DRAFT, TransactionStatus::NOT_APPROVED], true);
    }
}
