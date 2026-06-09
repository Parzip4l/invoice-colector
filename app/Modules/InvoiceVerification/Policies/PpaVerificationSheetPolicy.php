<?php

namespace App\Modules\InvoiceVerification\Policies;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet;

class PpaVerificationSheetPolicy
{
    public function view(User $user, PpaVerificationSheet $sheet): bool
    {
        return $user->can('view', $sheet->transaction);
    }

    public function update(User $user, PpaVerificationSheet|string $sheet): bool
    {
        return $user->hasRole(RoleCode::USER_DIVISI);
    }

    public function approve(User $user, PpaVerificationSheet $sheet): bool
    {
        return $user->hasRole(RoleCode::KEPALA_DIVISI)
            && $user->division_id === $sheet->transaction->division_id;
    }
}
