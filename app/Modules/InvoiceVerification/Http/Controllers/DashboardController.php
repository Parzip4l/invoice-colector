<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
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
        $summary = $this->dashboardService->summary();
        $recentTransactions = Transaction::query()
            ->with(['transactionType', 'vendor', 'division', 'department'])
            ->latest()
            ->limit(6)
            ->get();
        $approvalQueue = ApprovalTransaction::query()
            ->with(['transaction', 'approvalFlow'])
            ->where('status', 'PENDING')
            ->latest()
            ->limit(6)
            ->get();

        return view('invoice-verification.dashboard.index', compact('summary', 'recentTransactions', 'approvalQueue'));
    }
}
