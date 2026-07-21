<?php

namespace App\Modules\InvoiceVerification\Services\Eproc;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\Bank;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\Division;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class EprocImportService
{
    public function import(
        iterable $vendorRows = [],
        iterable $purchasingRows = [],
        ?User $createdBy = null,
        string $divisionCode = 'EPROC',
        string $divisionName = 'E-Procurement',
    ): array {
        return DB::transaction(function () use ($vendorRows, $purchasingRows, $createdBy, $divisionCode, $divisionName) {
            $stats = [
                'vendor_rows' => 0,
                'purchasing_rows' => 0,
                'purchasing_po_rows' => 0,
                'vendors_created' => 0,
                'vendors_updated' => 0,
                'banks_created' => 0,
                'agreements_created' => 0,
                'agreements_updated' => 0,
                'departments_created' => 0,
            ];

            foreach ($vendorRows as $row) {
                $stats['vendor_rows']++;
                $this->importVendorRow($row, $stats);
            }

            $purchaseOrders = $this->groupPurchasingRows($purchasingRows, $stats);

            if ($purchaseOrders !== []) {
                $division = Division::query()->firstOrCreate(
                    ['ldap_code' => $this->clean($divisionCode) ?: 'EPROC'],
                    [
                        'name' => $this->clean($divisionName) ?: 'E-Procurement',
                        'is_active' => true,
                        'last_synced_at' => now(),
                    ],
                );

                foreach ($purchaseOrders as $row) {
                    $this->importPurchaseOrder($row, $division, $createdBy, $stats);
                }
            }

            return $stats;
        });
    }

    private function importVendorRow(array $row, array &$stats): void
    {
        $vendorName = $this->clean($row['Nama Vendor'] ?? null);

        if ($vendorName === null) {
            return;
        }

        $bank = null;
        $bankName = $this->clean($row['Nama Bank'] ?? $row['Bank'] ?? null);

        if ($bankName !== null) {
            $bank = Bank::query()->firstOrCreate(
                ['name' => $bankName],
                ['code' => $this->makeCode($bankName)],
            );

            if ($bank->wasRecentlyCreated) {
                $stats['banks_created']++;
            }
        }

        $vendorCode = $this->clean($row['Nomor Eproc'] ?? null);
        $vendor = Vendor::query()
            ->when($vendorCode !== null, fn ($query) => $query->where('vendor_code', $vendorCode))
            ->when($vendorCode === null, fn ($query) => $query->whereRaw('LOWER(name) = ?', [Str::lower($vendorName)]))
            ->first();

        $payload = [
            'vendor_code' => $vendorCode,
            'name' => $vendorName,
            'npwp' => $this->clean($row['NPWP'] ?? null),
            'address' => $this->clean($row['Alamat Vendor'] ?? $row['Alamat Operasional'] ?? null),
            'contact_name' => $this->clean($row['Nama PIC'] ?? null),
            'contact_email' => Str::lower((string) $this->clean($row['Email PIC'] ?? $row['Email'] ?? null)) ?: null,
            'contact_phone' => $this->clean($row['Nomor PIC'] ?? $row['Nomor Telpon'] ?? null),
            'default_bank_id' => $bank?->id,
            'default_account_number' => $this->clean($row['Nomor Rekening'] ?? $row['Account Number'] ?? null),
        ];

        if ($vendor) {
            $vendor->forceFill($payload)->save();
            $stats['vendors_updated']++;

            return;
        }

        Vendor::query()->create($payload);
        $stats['vendors_created']++;
    }

    private function groupPurchasingRows(iterable $rows, array &$stats): array
    {
        $purchaseOrders = [];

        foreach ($rows as $row) {
            $stats['purchasing_rows']++;
            $contractNumber = $this->clean($row['Nomor PO'] ?? null);

            if ($contractNumber === null) {
                continue;
            }

            if (! isset($purchaseOrders[$contractNumber])) {
                $purchaseOrders[$contractNumber] = [
                    'Nomor PO' => $contractNumber,
                    'Nomor Pengadaan' => $this->clean($row['Nomor Pengadaan'] ?? null),
                    'Nama Pengadaan' => $this->clean($row['Nama Pengadaan'] ?? null) ?: $contractNumber,
                    'Tanggal PO' => $row['Tanggal PO'] ?? null,
                    'Departemen' => $this->clean($row['Departemen'] ?? null),
                    'Nama Vendor' => $this->clean($row['Nama Vendor'] ?? null),
                    'Total Harga' => '0.00',
                ];
            }

            $currentTotal = (float) ($purchaseOrders[$contractNumber]['Total Harga'] ?? 0);
            $rowTotal = $this->parseNumber($row['Total Harga'] ?? $row['contract_value'] ?? null);

            if ($rowTotal !== null) {
                $purchaseOrders[$contractNumber]['Total Harga'] = number_format($currentTotal + (float) $rowTotal, 2, '.', '');
            }
        }

        $stats['purchasing_po_rows'] = count($purchaseOrders);

        return array_values($purchaseOrders);
    }

    private function importPurchaseOrder(array $row, Division $division, ?User $createdBy, array &$stats): void
    {
        $contractNumber = $this->clean($row['Nomor PO'] ?? null);

        if ($contractNumber === null) {
            return;
        }

        $vendorName = $this->clean($row['Nama Vendor'] ?? null);
        $vendor = $vendorName
            ? Vendor::query()->whereRaw('LOWER(name) = ?', [Str::lower($vendorName)])->first()
            : null;

        if (! $vendor && $vendorName) {
            $vendor = Vendor::query()->create(['name' => $vendorName]);
            $stats['vendors_created']++;
        }

        $departmentName = $this->clean($row['Departemen'] ?? null) ?: 'E-Procurement';
        $department = Department::query()
            ->where('division_id', $division->id)
            ->whereRaw('LOWER(name) = ?', [Str::lower($departmentName)])
            ->first();

        if (! $department) {
            $department = Department::query()->create([
                'division_id' => $division->id,
                'ldap_code' => null,
                'name' => $departmentName,
                'is_active' => true,
                'last_synced_at' => now(),
            ]);
            $stats['departments_created']++;
        }

        $agreement = AgreementReference::query()->where('contract_number', $contractNumber)->first();
        $payload = [
            'vendor_id' => $vendor?->id,
            'division_id' => $division->id,
            'department_id' => $department->id,
            'contract_number' => $contractNumber,
            'title' => $this->clean($row['Nama Pengadaan'] ?? null) ?: $contractNumber,
            'contract_value' => $this->parseNumber($row['Total Harga'] ?? null),
            'effective_date' => $this->parseDate($row['Tanggal PO'] ?? null),
            'created_by' => $createdBy?->id,
        ];

        if ($agreement) {
            $agreement->forceFill($payload)->save();
            $stats['agreements_updated']++;

            return;
        }

        AgreementReference::query()->create($payload);
        $stats['agreements_created']++;
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseNumber(mixed $value): ?string
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        $number = preg_replace('/[^0-9.-]/', '', $value);

        return $number === '' ? null : number_format((float) $number, 2, '.', '');
    }

    private function parseDate(mixed $value): ?string
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        if (is_numeric($value) && (float) $value > 25000 && (float) $value < 80000) {
            return Carbon::create(1899, 12, 30)->addDays((int) floor((float) $value))->toDateString();
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function makeCode(string $value): string
    {
        return strtoupper(Str::of($value)->slug('_')->limit(30, '')->toString());
    }
}
