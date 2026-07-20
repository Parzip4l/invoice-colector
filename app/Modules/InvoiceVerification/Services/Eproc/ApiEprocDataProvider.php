<?php

namespace App\Modules\InvoiceVerification\Services\Eproc;

use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use App\Modules\InvoiceVerification\Services\Contracts\EprocDataProviderInterface;
use Illuminate\Support\Collection;
use RuntimeException;

class ApiEprocDataProvider implements EprocDataProviderInterface
{
    public function getVendor(string $vendorId): ?Vendor
    {
        $this->notConfigured();
    }

    public function getContract(string $contractId): ?AgreementReference
    {
        $this->notConfigured();
    }

    public function getSupportingDocuments(Transaction $transaction): Collection
    {
        $this->notConfigured();
    }

    private function notConfigured(): never
    {
        throw new RuntimeException('API eProc belum dikonfigurasi. Gunakan driver local atau lengkapi konfigurasi API.');
    }
}
