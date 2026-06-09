<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Services\GeneratedDocumentService;
use App\Modules\InvoiceVerification\Services\PpaVerificationSheetService;
use App\Modules\InvoiceVerification\Services\TransactionLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminDocumentGenerationController extends Controller
{
    public function __construct(
        protected GeneratedDocumentService $generatedDocumentService,
        protected PpaVerificationSheetService $ppaVerificationSheetService,
        protected TransactionLifecycleService $transactionLifecycleService,
    ) {
    }

    public function store(Request $request, Transaction $transaction): RedirectResponse
    {
        abort_unless(
            $request->user()?->hasRole(RoleCode::ADMIN_DIVISI)
                && $request->user()?->division_id === $transaction->division_id
                && $transaction->status === TransactionStatus::ADMIN_GENERATE_DOCUMENTS,
            403,
        );

        $transaction->loadMissing('transactionType', 'latestDocuments.documentType', 'invoiceMetadata');

        if ($transaction->generatedDocuments()->doesntExist()) {
            $this->generatedDocumentService->generateInitialDocument($transaction, $request->user());
        }

        if ($transaction->isPpa()) {
            $this->ppaVerificationSheetService->generateFromAcceptedDocuments($transaction, $request->user());
        }

        $this->transactionLifecycleService->transition(
            $transaction,
            TransactionStatus::ACCOUNTING_VERIFICATION,
            TransactionStep::ACCOUNTING_ADMINISTRATION,
            $request->user(),
            'Admin User telah generate Lembar PPA dan Lembar Verifikasi. Transaksi diteruskan ke Akuntansi.',
        );

        return redirect()
            ->route('invoice-verification.transactions.show', $transaction)
            ->with('success', 'Lembar PPA dan Lembar Verifikasi berhasil digenerate dan dikirim ke Akuntansi.');
    }
}
