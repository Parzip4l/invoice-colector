<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Http\Requests\StoreAccountingVerificationRequest;
use App\Modules\InvoiceVerification\Services\AccountingVerificationService;

class AccountingVerificationController extends Controller
{
    public function __construct(
        protected AccountingVerificationService $accountingVerificationService,
    ) {
    }

    public function edit(Transaction $transaction)
    {
        $this->authorize('verifyAccounting', $transaction);

        $transaction->load(['generatedDocuments', 'ppaVerificationSheet']);
        $verification = $this->accountingVerificationService->getOrCreate($transaction, request()->user());

        return view('invoice-verification.accounting-verifications.edit', compact('transaction', 'verification'));
    }

    public function update(StoreAccountingVerificationRequest $request, Transaction $transaction)
    {
        $this->accountingVerificationService->verify(
            $transaction,
            $request->user(),
            $request->validated('items'),
            $request->validated('administration_status'),
            $request->validated('administration_notes'),
            $request->validated('notes'),
        );

        return redirect()
            ->route('invoice-verification.transactions.show', $transaction)
            ->with('success', 'Verifikasi akuntansi berhasil diproses.');
    }
}
