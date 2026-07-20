<?php

namespace App\Modules\InvoiceVerification\Services\Contracts;

use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use Illuminate\Support\Collection;

interface EprocDataProviderInterface
{
    public function getVendor(string $vendorId): ?Vendor;

    public function getContract(string $contractId): ?AgreementReference;

    /**
     * @return Collection<int, array{code: string, label: string, source_type: string, file_path: ?string, file_disk: ?string, read_only: bool}>
     */
    public function getSupportingDocuments(Transaction $transaction): Collection;
}
