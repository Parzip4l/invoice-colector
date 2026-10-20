<?php

namespace App\Modules\InvoiceVerification\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BudgetPlanCodeService
{
    protected string $baseUrl;
    protected int $timeout;
    protected bool $verifySsl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('invoice_verification.budget_api.base_url', 'https://versus.lrtjakarta.co.id'), '/');
        $this->timeout = (int) config('invoice_verification.budget_api.timeout', 10);
        $this->verifySsl = (bool) config('invoice_verification.budget_api.verify_ssl', true);
    }

    /**
     * Search budget plan codes with query string and optional filters.
     *
     * @param  string  $query
     * @param  array  $filters
     * @return array
     */
    public function search(string $query = '', array $filters = []): array
    {
        if (blank($this->baseUrl)) {
            return [];
        }

        $params = array_filter([
            'q' => $query ?: null,
            'active_only' => 1,
            'per_page' => $filters['per_page'] ?? 50,
            'page' => $filters['page'] ?? 1,
            'department_code' => $filters['department_code'] ?? null,
            'category' => $filters['category'] ?? null,
            'data_area_id' => $filters['data_area_id'] ?? null,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withOptions(['verify' => $this->verifySsl])
                ->get("{$this->baseUrl}/api/v1/budget-plan-codes", $params);

            if (! $response->successful()) {
                Log::warning('BudgetPlanCodeService: search request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $json = $response->json();
            $items = $json['data'] ?? [];

            return collect($items)->map(function ($item) {
                $rabCode = $item['rab_code'] ?? '';
                $codeName = $item['code_name'] ?? '';
                $dept = $item['department_code'] ?? '';

                return [
                    'id' => $item['id'] ?? null,
                    'rab_code' => $rabCode,
                    'code_name' => $codeName,
                    'department_code' => $dept,
                    'department_name' => $item['department_name'] ?? '',
                    'kode_subsidi' => $item['kode_subsidi'] ?? '',
                    'category' => $item['category'] ?? '',
                    'capex_opex' => $item['capex_opex'] ?? '',
                    'status' => $item['status'] ?? 'Active',
                    'label' => $rabCode.($codeName ? ' - '.$codeName : '').($dept && $dept !== 'NONE' ? " ({$dept})" : ''),
                ];
            })->all();
        } catch (\Throwable $e) {
            Log::error('BudgetPlanCodeService: exception occurred while searching', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            return [];
        }
    }

    /**
     * Find a budget plan code by rab_code or id.
     *
     * @param  string  $identifier
     * @return array|null
     */
    public function find(string $identifier): ?array
    {
        if (blank($this->baseUrl) || blank($identifier)) {
            return null;
        }

        try {
            $encoded = urlencode(trim($identifier));
            $response = Http::timeout($this->timeout)
                ->withOptions(['verify' => $this->verifySsl])
                ->get("{$this->baseUrl}/api/v1/budget-plan-codes/{$encoded}");

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();
            $data = $json['data'] ?? null;

            if (! $data) {
                return null;
            }

            return [
                'id' => $data['id'] ?? null,
                'rab_code' => $data['rab_code'] ?? '',
                'code_name' => $data['code_name'] ?? '',
                'department_code' => $data['department_code'] ?? '',
                'department_name' => $data['department_name'] ?? '',
                'kode_subsidi' => $data['kode_subsidi'] ?? '',
                'category' => $data['category'] ?? '',
                'status' => $data['status'] ?? 'Active',
            ];
        } catch (\Throwable $e) {
            Log::error('BudgetPlanCodeService: exception while fetching detail', [
                'error' => $e->getMessage(),
                'identifier' => $identifier,
            ]);

            return null;
        }
    }
}
