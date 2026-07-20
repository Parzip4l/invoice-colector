<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Models\CompiledDocument;
use Illuminate\Http\Request;

class CompiledDocumentController extends Controller
{
    public function index(Request $request)
    {
        $sort = in_array($request->query('sort'), ['transaction', 'compiled_file_name', 'total_files', 'compiled_at'], true)
            ? $request->query('sort')
            : 'compiled_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));

        $compiledQuery = CompiledDocument::query()
            ->select('compiled_documents.*')
            ->with('transaction.transactionType')
            ->when($search !== '', function ($query) use ($search) {
                $needle = '%'.mb_strtolower($search).'%';

                $query->where(function ($innerQuery) use ($needle) {
                    $innerQuery
                        ->whereRaw('LOWER(compiled_documents.compiled_file_name) LIKE ?', [$needle])
                        ->orWhereHas('transaction', fn ($transactionQuery) => $transactionQuery->whereRaw('LOWER(registration_number) LIKE ?', [$needle]));
                });
            });

        if ($sort === 'transaction') {
            $compiledQuery
                ->leftJoin('transactions', 'transactions.id', '=', 'compiled_documents.transaction_id')
                ->orderBy('transactions.registration_number', $direction);
        } else {
            $compiledQuery->orderBy('compiled_documents.'.$sort, $direction);
        }

        $compiledDocuments = $compiledQuery
            ->paginate(10)
            ->withQueryString();

        return view('invoice-verification.compiled-documents.index', compact('compiledDocuments', 'sort', 'direction', 'search'));
    }

    public function show(CompiledDocument $compiledDocument)
    {
        $compiledDocument->load(['transaction', 'items', 'compiler', 'archivedBy']);

        return view('invoice-verification.compiled-documents.show', compact('compiledDocument'));
    }
}
