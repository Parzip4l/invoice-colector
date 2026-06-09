<?php

namespace App\Modules\InvoiceVerification\Services\Implementations;

use App\Modules\InvoiceVerification\Services\Contracts\LdapDirectorySynchronizer;

class NullLdapDirectorySynchronizer implements LdapDirectorySynchronizer
{
    public function syncAll(): array
    {
        return [
            'status' => 'NOT_IMPLEMENTED',
            'message' => 'LDAP connector is intentionally deferred. This service is the integration seam for a future adapter.',
            'synced_at' => now()->toIso8601String(),
        ];
    }
}
