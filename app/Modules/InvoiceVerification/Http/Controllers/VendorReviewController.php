<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Actions\ReviewVendorDocumentAction;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\VendorDocumentReviewStatus;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;
use App\Modules\InvoiceVerification\Http\Requests\ReviewVendorDocumentRequest;
use Illuminate\Http\Request;

class VendorReviewController extends Controller
{
    public function __construct(
        protected ReviewVendorDocumentAction $reviewVendorDocumentAction,
    ) {
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()?->hasRole(RoleCode::ADMIN_DIVISI), 403);

        $sort = in_array($request->query('sort'), ['transaction', 'document', 'vendor', 'version', 'uploaded_at'], true)
            ? $request->query('sort')
            : 'uploaded_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));

        $documentQuery = TransactionDocument::query()
            ->select('transaction_documents.*')
            ->with(['transaction.transactionType', 'transaction.vendor', 'documentType'])
            ->where('source_actor', 'VENDOR')
            ->where('status', 'UNDER_REVIEW')
            ->whereHas('transaction', fn ($query) => $query->where('division_id', auth()->user()?->division_id))
            ->when($search !== '', function ($query) use ($search) {
                $needle = '%'.mb_strtolower($search).'%';

                $query->where(function ($innerQuery) use ($needle) {
                    $innerQuery
                        ->whereRaw('LOWER(transaction_documents.document_label) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(transaction_documents.file_name) LIKE ?', [$needle])
                        ->orWhereHas('transaction', function ($transactionQuery) use ($needle) {
                            $transactionQuery
                                ->whereRaw('LOWER(registration_number) LIKE ?', [$needle])
                                ->orWhereRaw('LOWER(title) LIKE ?', [$needle])
                                ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->whereRaw('LOWER(name) LIKE ?', [$needle]));
                        })
                        ->orWhereHas('documentType', fn ($documentTypeQuery) => $documentTypeQuery->whereRaw('LOWER(name) LIKE ?', [$needle]));
                });
            });

        if ($sort === 'transaction') {
            $documentQuery
                ->leftJoin('transactions', 'transactions.id', '=', 'transaction_documents.transaction_id')
                ->orderBy('transactions.registration_number', $direction);
        } elseif ($sort === 'document') {
            $documentQuery->orderBy('transaction_documents.document_label', $direction);
        } elseif ($sort === 'vendor') {
            $documentQuery
                ->leftJoin('transactions as vendor_transactions', 'vendor_transactions.id', '=', 'transaction_documents.transaction_id')
                ->leftJoin('vendors', 'vendors.id', '=', 'vendor_transactions.vendor_id')
                ->orderBy('vendors.name', $direction);
        } else {
            $documentQuery->orderBy('transaction_documents.'.$sort, $direction);
        }

        $pendingDocuments = $documentQuery
            ->paginate(10)
            ->withQueryString();

        return view('invoice-verification.vendor-reviews.index', compact('pendingDocuments', 'sort', 'direction', 'search'));
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
