<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Http\Requests\ProcessFinanceTransactionRequest;
use App\Modules\InvoiceVerification\Services\FinalizationService;

class FinanceController extends Controller
{
    public function __construct(
        protected FinalizationService $finalizationService,
    ) {
    }

    public function index()
    {
        abort_unless(auth()->user()?->hasRole(RoleCode::FINANCE), 403);

        $transactions = Transaction::query()
            ->with(['transactionType', 'vendor', 'numberingRegister', 'compiledDocument'])
            ->where('status', TransactionStatus::FINANCE_PROCESS)
            ->latest()
            ->paginate(10);

        return view('invoice-verification.finance.index', compact('transactions'));
    }

    public function update(ProcessFinanceTransactionRequest $request, Transaction $transaction)
    {
        $this->finalizationService->completeFinanceProcessing(
            $transaction,
            $request->user(),
            $request->validated('notes'),
        );

        return redirect()
            ->route('invoice-verification.finance.index')
            ->with('success', 'Proses finance berhasil diselesaikan dan transaksi dipindahkan ke arsip final.');
    }
}
