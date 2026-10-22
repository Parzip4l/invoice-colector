<?php

namespace App\Modules\InvoiceVerification\Services\Contracts;

use App\Modules\InvoiceVerification\Domain\Models\Transaction;

interface DocumentCompiler
{
    /**
     * Build a compiled PDF in temporary storage for a single download response.
     *
     * @return array{file_name: string, temporary_path: string, total_files: int}
     */
    public function compileTemporary(Transaction $transaction, array $documents): array;
}
