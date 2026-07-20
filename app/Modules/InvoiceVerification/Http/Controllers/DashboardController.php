<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {
    }

    public function index()
    {
        if (auth()->user()?->hasRole(RoleCode::VENDOR)) {
            return view('invoice-verification.dashboard.vendor', $this->dashboardService->vendorDashboard(auth()->user()));
        }

        $summary = $this->dashboardService->summary();
        $analytics = $this->dashboardService->analytics();
        $recentTransactions = Transaction::query()
            ->with(['transactionType', 'vendor', 'division', 'department'])
            ->latest()
            ->limit(6)
            ->get();

        return view('invoice-verification.dashboard.index', compact('summary', 'analytics', 'recentTransactions'));
    }
}
