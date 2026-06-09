<?php

namespace App\Modules\InvoiceVerification\Policies;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Models\GeneratedDocument;

class GeneratedDocumentPolicy
{
    public function view(User $user, GeneratedDocument $generatedDocument): bool
    {
        return $user->can('view', $generatedDocument->transaction);
    }
}
