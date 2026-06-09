<?php

namespace App\Modules\InvoiceVerification\Policies;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(
            RoleCode::ADMIN_DIVISI,
            RoleCode::KEPALA_DEPARTEMEN,
            RoleCode::KEPALA_DIVISI,
            RoleCode::USER_DIVISI,
            RoleCode::VENDOR,
            RoleCode::AKUNTANSI,
            RoleCode::FINANCE,
        );
    }

    public function view(User $user, Transaction $transaction): bool
    {
        if ($user->hasRole(RoleCode::VENDOR)) {
            return $transaction->vendor_id !== null
                && $transaction->vendor_id === $user->linkedVendor()?->id;
        }

        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleCode::ADMIN_DIVISI);
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->hasRole(RoleCode::ADMIN_DIVISI, RoleCode::USER_DIVISI)
            && $user->division_id === $transaction->division_id;
    }

    public function uploadDocuments(User $user, Transaction $transaction): bool
    {
        if ($transaction->isPpa()) {
            return $user->hasRole(RoleCode::VENDOR)
                && $transaction->vendor_id === $user->linkedVendor()?->id
                && in_array($transaction->status?->value, [
                    TransactionStatus::DRAFT->value,
                    TransactionStatus::VENDOR_INPUT->value,
                    TransactionStatus::REVISION_IN_PROGRESS->value,
                ], true);
        }

        return $user->hasRole(RoleCode::ADMIN_DIVISI, RoleCode::USER_DIVISI, RoleCode::VENDOR)
            && ($user->division_id === $transaction->division_id || $user->hasRole(RoleCode::VENDOR));
    }

    public function verifyAccounting(User $user, Transaction $transaction): bool
    {
        return $user->hasRole(RoleCode::AKUNTANSI);
    }

    public function processFinance(User $user, Transaction $transaction): bool
    {
        return $user->hasRole(RoleCode::FINANCE);
    }

    public function manageMasterData(User $user): bool
    {
        return $user->hasRole(RoleCode::ADMIN_DIVISI);
    }

    public function createMemoRequest(User $user): bool
    {
        return $user->hasRole(RoleCode::ADMIN_DIVISI);
    }

    public function createAgreementReference(User $user): bool
    {
        return $user->hasRole(RoleCode::ADMIN_DIVISI);
    }
}
