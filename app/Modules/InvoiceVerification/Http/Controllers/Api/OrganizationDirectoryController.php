<?php

namespace App\Modules\InvoiceVerification\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\Division;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationDirectoryController extends Controller
{
    public function divisions(Request $request): JsonResponse
    {
        $includeInactive = $request->boolean('include_inactive');

        $divisions = Division::query()
            ->withCount('departments')
            ->when(! $includeInactive, fn ($query) => $query->where('is_active', true))
            ->when($request->string('search')->toString(), function ($query, string $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('ldap_code', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Division $division) => $this->divisionPayload($division));

        return response()->json([
            'data' => $divisions,
            'meta' => [
                'count' => $divisions->count(),
                'include_inactive' => $includeInactive,
            ],
        ]);
    }

    public function departments(Request $request): JsonResponse
    {
        $includeInactive = $request->boolean('include_inactive');

        $departments = Department::query()
            ->with('division')
            ->when(! $includeInactive, function ($query) {
                $query->where('is_active', true)
                    ->whereHas('division', fn ($divisionQuery) => $divisionQuery->where('is_active', true));
            })
            ->when($request->string('division_id')->toString(), fn ($query, string $divisionId) => $query->where('division_id', $divisionId))
            ->when($request->string('division_ldap_code')->toString(), function ($query, string $ldapCode) {
                $query->whereHas('division', fn ($divisionQuery) => $divisionQuery->where('ldap_code', $ldapCode));
            })
            ->when($request->string('search')->toString(), function ($query, string $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('ldap_code', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('division_id')
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department) => $this->departmentPayload($department));

        return response()->json([
            'data' => $departments,
            'meta' => [
                'count' => $departments->count(),
                'include_inactive' => $includeInactive,
            ],
        ]);
    }

    public function tree(Request $request): JsonResponse
    {
        $includeInactive = $request->boolean('include_inactive');

        $divisions = Division::query()
            ->with(['departments' => function ($query) use ($includeInactive) {
                $query->when(! $includeInactive, fn ($departmentQuery) => $departmentQuery->where('is_active', true))
                    ->orderBy('name');
            }])
            ->when(! $includeInactive, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get()
            ->map(function (Division $division) {
                return [
                    ...$this->divisionPayload($division),
                    'departments' => $division->departments
                        ->map(fn (Department $department) => $this->departmentPayload($department, includeDivision: false))
                        ->values(),
                ];
            });

        return response()->json([
            'data' => $divisions,
            'meta' => [
                'count' => $divisions->count(),
                'include_inactive' => $includeInactive,
            ],
        ]);
    }

    protected function divisionPayload(Division $division): array
    {
        return [
            'id' => $division->id,
            'ldap_code' => $division->ldap_code,
            'name' => $division->name,
            'is_active' => (bool) $division->is_active,
            'departments_count' => $division->departments_count ?? $division->departments()->count(),
            'updated_at' => $division->updated_at?->toISOString(),
        ];
    }

    protected function departmentPayload(Department $department, bool $includeDivision = true): array
    {
        $payload = [
            'id' => $department->id,
            'division_id' => $department->division_id,
            'ldap_code' => $department->ldap_code,
            'name' => $department->name,
            'is_active' => (bool) $department->is_active,
            'updated_at' => $department->updated_at?->toISOString(),
        ];

        if ($includeDivision) {
            $payload['division'] = $department->division ? [
                'id' => $department->division->id,
                'ldap_code' => $department->division->ldap_code,
                'name' => $department->division->name,
                'is_active' => (bool) $department->division->is_active,
            ] : null;
        }

        return $payload;
    }
}
