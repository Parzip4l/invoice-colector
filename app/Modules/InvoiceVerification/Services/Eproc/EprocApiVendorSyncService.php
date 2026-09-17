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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class EprocApiVendorSyncService
{
    public function sync(?User $createdBy = null, string $divisionCode = 'EPROC', string $divisionName = 'E-Procurement'): array
    {
        $vendors = $this->fetchVendors();

        return DB::transaction(function () use ($vendors, $createdBy, $divisionCode, $divisionName) {
            $stats = [
                'vendor_rows' => 0,
                'vendors_created' => 0,
                'vendors_updated' => 0,
                'banks_created' => 0,
                'purchase_order_rows' => 0,
                'agreements_created' => 0,
                'agreements_updated' => 0,
                'departments_created' => 0,
            ];

            $division = Division::query()->firstOrCreate(
                ['ldap_code' => $this->clean($divisionCode) ?: 'EPROC'],
                [
                    'name' => $this->clean($divisionName) ?: 'E-Procurement',
                    'is_active' => true,
                    'last_synced_at' => now(),
                ],
            );

            $department = Department::query()
                ->where('division_id', $division->id)
                ->whereRaw('LOWER(name) = ?', [Str::lower('E-Procurement')])
                ->first();

            if (! $department) {
                $department = Department::query()->create([
                    'division_id' => $division->id,
                    'ldap_code' => null,
                    'name' => 'E-Procurement',
                    'is_active' => true,
                    'last_synced_at' => now(),
                ]);
                $stats['departments_created']++;
            }

            foreach ($vendors as $row) {
                $stats['vendor_rows']++;
                $vendor = $this->syncVendor($row, $stats);

                foreach (($row['purchase_order'] ?? []) as $purchaseOrder) {
                    if (! is_array($purchaseOrder)) {
                        continue;
                    }

                    $stats['purchase_order_rows']++;
                    $this->syncPurchaseOrder($purchaseOrder, $vendor, $division, $department, $createdBy, $stats);
                }
            }

            return $stats;
        });
    }

    public function fetchVendors(): array
    {
        $baseUrl = rtrim((string) config('invoice_verification.eproc.base_url'), '/');
        $endpoint = (string) config('invoice_verification.eproc.vendor_endpoint', 'detail_purchase_order_vendor');
        $token = (string) config('invoice_verification.eproc.token');

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('Konfigurasi EPROC_BASE_URL dan EPROC_TOKEN wajib diisi untuk sync API eProc.');
        }

        $response = Http::timeout((int) config('invoice_verification.eproc.timeout', 30))
            ->acceptJson()
            ->withHeaders(['token' => $token])
            ->get($baseUrl.'/'.ltrim($endpoint, '/'));

        if (! $response->successful()) {
            throw new RuntimeException('Sync vendor eProc gagal. HTTP '.$response->status());
        }

        $payload = $response->json();

        if (data_get($payload, 'response.status') !== true) {
            throw new RuntimeException((string) data_get($payload, 'response.message', 'Response eProc tidak valid.'));
        }

        $vendors = data_get($payload, 'results.vendor', []);

        if (! is_array($vendors)) {
            throw new RuntimeException('Response eProc tidak memiliki results.vendor yang valid.');
        }

        return $vendors;
    }

    private function syncVendor(array $row, array &$stats): Vendor
    {
        $vendorName = $this->clean($row['vendor_name'] ?? null) ?: 'Vendor eProc';
        $vendorCode = $this->clean($row['erp_vendor_code'] ?? $row['eproc_number'] ?? null);
        $bank = $this->resolveBank($this->clean($row['bank_name'] ?? $row['nama_bank'] ?? null), $stats);

        $vendor = Vendor::query()
            ->when($vendorCode !== null, fn ($query) => $query->where('vendor_code', $vendorCode))
            ->when($vendorCode === null, fn ($query) => $query->whereRaw('LOWER(name) = ?', [Str::lower($vendorName)]))
            ->first();

        $payload = [
            'vendor_code' => $vendorCode,
            'name' => $vendorName,
            'npwp' => $this->clean($row['npwp_company'] ?? null),
            'address' => $this->clean($row['vendor_address_operational'] ?? $row['vendor_address'] ?? null),
            'contact_name' => $this->clean($row['contact_person_name'] ?? null),
            'contact_email' => Str::lower((string) $this->clean($row['contact_person_email'] ?? $row['email'] ?? null)) ?: null,
            'contact_phone' => $this->clean($row['contact_person_mobile_phone'] ?? $row['phone_number'] ?? null),
            'default_bank_id' => $bank?->id,
            'default_account_number' => $this->digits($row['account_number'] ?? $row['nomor_rekening'] ?? null),
            'updated_at' => now(),
        ];

        if ($vendor) {
            $vendor->forceFill($payload)->save();
            $stats['vendors_updated']++;

            return $vendor;
        }

        $stats['vendors_created']++;

        return Vendor::query()->create($payload);
    }

    private function syncPurchaseOrder(array $row, Vendor $vendor, Division $division, Department $department, ?User $createdBy, array &$stats): void
    {
        $contractNumber = $this->clean($row['purchase_order_number'] ?? $row['erp_purchase_order_id'] ?? null);

        if ($contractNumber === null) {
            return;
        }

        $agreement = AgreementReference::query()->where('contract_number', $contractNumber)->first();
        $payload = [
            'vendor_id' => $vendor->id,
            'division_id' => $division->id,
            'department_id' => $department->id,
            'contract_number' => $contractNumber,
            'title' => Str::limit($this->purchaseOrderTitle($row), 255, ''),
            'contract_value' => $this->purchaseOrderValue($row),
            'effective_date' => $this->parseDate($row['purchase_order_date'] ?? $row['start_date'] ?? $row['created_at'] ?? null),
            'expired_at' => $this->parseDate($row['end_date'] ?? $row['delivery_date'] ?? null),
            'created_by' => $agreement?->created_by ?? $createdBy?->id,
        ];

        if ($agreement) {
            $agreement->forceFill($payload)->save();
            $stats['agreements_updated']++;

            return;
        }

        AgreementReference::query()->create($payload);
        $stats['agreements_created']++;
    }

    private function resolveBank(?string $bankName, array &$stats): ?Bank
    {
        if ($bankName === null) {
            return null;
        }

        $bank = Bank::query()->firstOrCreate(
            ['name' => $bankName],
            ['code' => strtoupper(Str::of($bankName)->slug('_')->limit(30, '')->toString())],
        );

        if ($bank->wasRecentlyCreated) {
            $stats['banks_created']++;
        }

        return $bank;
    }

    private function purchaseOrderTitle(array $row): string
    {
        $items = collect($row['purchase_order_item'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(fn ($item) => $this->clean($item['description'] ?? $item['item_name'] ?? null))
            ->filter()
            ->take(2)
            ->implode(', ');

        return $items !== '' ? $items : ($this->clean($row['purchase_order_number'] ?? null) ?: 'Purchase Order eProc');
    }

    private function purchaseOrderValue(array $row): ?string
    {
        $items = $row['purchase_order_item'] ?? [];

        if (! is_array($items)) {
            return null;
        }

        $total = collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(fn ($item) => (float) ($this->parseNumber($item['line_amount'] ?? null) ?? 0))
            ->sum();

        return $total > 0 ? number_format($total, 2, '.', '') : null;
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function digits(mixed $value): ?string
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits === '' ? null : $digits;
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

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
