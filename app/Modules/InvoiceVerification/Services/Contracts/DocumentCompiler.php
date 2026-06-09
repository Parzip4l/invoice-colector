<?php

namespace App\Modules\InvoiceVerification\Services\Contracts;

use App\Modules\InvoiceVerification\Domain\Models\Transaction;

interface DocumentCompiler
{
    public function compile(Transaction $transaction, array $documents, string $targetDirectory): array;
}
