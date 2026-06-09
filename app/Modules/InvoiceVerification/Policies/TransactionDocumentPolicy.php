<?php

namespace App\Modules\InvoiceVerification\Policies;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;

class TransactionDocumentPolicy
{
    public function view(User $user, TransactionDocument $transactionDocument): bool
    {
        return $user->can('view', $transactionDocument->transaction);
    }

    public function reviewVendorDocument(User $user, TransactionDocument $transactionDocument): bool
    {
        return $user->hasRole(RoleCode::ADMIN_DIVISI)
            && $transactionDocument->transaction->division_id === $user->division_id;
    }
}
