<?php

namespace App\Modules\InvoiceVerification\Services\Contracts;

interface LdapDirectorySynchronizer
{
    public function syncAll(): array;
}
