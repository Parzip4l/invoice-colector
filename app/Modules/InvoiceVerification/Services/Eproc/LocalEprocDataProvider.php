<?php

namespace App\Modules\InvoiceVerification\Services\Eproc;

use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use App\Modules\InvoiceVerification\Services\Contracts\EprocDataProviderInterface;
use Illuminate\Support\Collection;

class LocalEprocDataProvider implements EprocDataProviderInterface
{
    public function getVendor(string $vendorId): ?Vendor
    {
        return Vendor::query()->with('defaultBank')->find($vendorId);
    }

    public function getContract(string $contractId): ?AgreementReference
    {
        return AgreementReference::query()->find($contractId);
    }

    public function getSupportingDocuments(Transaction $transaction): Collection
    {
        $transaction->loadMissing('vendor.defaultBank', 'memoRequest', 'agreementReference');

        return collect([
            [
                'code' => 'BANK_ACCOUNT',
                'label' => trim(($transaction->vendor?->defaultBank?->name ?? '').' '.$transaction->vendor?->default_account_number) ?: 'Bank dan Nomor Rekening',
                'source_type' => 'local_master',
                'file_path' => null,
                'file_disk' => null,
                'read_only' => true,
            ],
            [
                'code' => 'NPWP',
                'label' => $transaction->vendor?->npwp ?: 'NPWP',
                'source_type' => 'local_master',
                'file_path' => null,
                'file_disk' => null,
                'read_only' => true,
            ],
            [
                'code' => 'MEMO_PERMOHONAN',
                'label' => $transaction->memoRequest?->memo_number ?: 'Memo Permohonan',
                'source_type' => 'local_master',
                'file_path' => $transaction->memoRequest?->file_path,
                'file_disk' => $transaction->memoRequest?->file_disk,
                'read_only' => true,
            ],
            [
                'code' => 'CONTRACT',
                'label' => $transaction->agreementReference?->contract_number ?: 'Kontrak PKS/SPK/PO',
                'source_type' => 'local_master',
                'file_path' => $transaction->agreementReference?->file_path,
                'file_disk' => $transaction->agreementReference?->file_disk,
                'read_only' => true,
            ],
        ]);
    }
}
