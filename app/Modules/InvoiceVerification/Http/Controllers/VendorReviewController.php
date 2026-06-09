<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Actions\ReviewVendorDocumentAction;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\VendorDocumentReviewStatus;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;
use App\Modules\InvoiceVerification\Http\Requests\ReviewVendorDocumentRequest;

class VendorReviewController extends Controller
{
    public function __construct(
        protected ReviewVendorDocumentAction $reviewVendorDocumentAction,
    ) {
    }

    public function index()
    {
        abort_unless(auth()->user()?->hasRole(RoleCode::ADMIN_DIVISI), 403);

        $pendingDocuments = TransactionDocument::query()
            ->with(['transaction.transactionType', 'transaction.vendor', 'documentType'])
            ->where('source_actor', 'VENDOR')
            ->where('status', 'UNDER_REVIEW')
            ->whereHas('transaction', fn ($query) => $query->where('division_id', auth()->user()?->division_id))
            ->latest('uploaded_at')
            ->paginate(10);

        return view('invoice-verification.vendor-reviews.index', compact('pendingDocuments'));
    }

    public function update(ReviewVendorDocumentRequest $request, TransactionDocument $transactionDocument)
    {
        abort_unless($request->user()?->hasRole(RoleCode::ADMIN_DIVISI), 403);

        $this->reviewVendorDocumentAction->execute(
            $transactionDocument,
            $request->user(),
            VendorDocumentReviewStatus::from($request->validated('status')),
            $request->validated('notes'),
        );

        return redirect()
            ->route('invoice-verification.vendor-reviews.index')
            ->with('success', 'Review dokumen vendor berhasil disimpan.');
    }
}
