<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Services\BudgetPlanCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetPlanCodeController extends Controller
{
    public function __construct(
        protected BudgetPlanCodeService $budgetPlanCodeService,
    ) {
    }

    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->input('q', $request->input('search', ''));
        $filters = [
            'department_code' => $request->input('department_code'),
            'category' => $request->input('category'),
            'per_page' => min((int) $request->input('per_page', 50), 100),
        ];

        $results = $this->budgetPlanCodeService->search($query, $filters);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    public function show(string $identifier): JsonResponse
    {
        $result = $this->budgetPlanCodeService->find($identifier);

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'Budget plan code tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
