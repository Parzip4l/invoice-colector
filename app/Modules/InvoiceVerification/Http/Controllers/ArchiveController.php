<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Models\CompiledDocument;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $sort = in_array($request->query('sort'), ['transaction', 'compiled_file_name', 'archive_path', 'archived_at'], true)
            ? $request->query('sort')
            : 'archived_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));

        $archiveQuery = CompiledDocument::query()
            ->select('compiled_documents.*')
            ->with('transaction.transactionType')
            ->whereNotNull('archived_at')
            ->when($search !== '', function ($query) use ($search) {
                $needle = '%'.mb_strtolower($search).'%';

                $query->where(function ($innerQuery) use ($needle) {
                    $innerQuery
                        ->whereRaw('LOWER(compiled_documents.compiled_file_name) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(compiled_documents.archive_path) LIKE ?', [$needle])
                        ->orWhereHas('transaction', fn ($transactionQuery) => $transactionQuery->whereRaw('LOWER(registration_number) LIKE ?', [$needle]));
                });
            });

        if ($sort === 'transaction') {
            $archiveQuery
                ->leftJoin('transactions', 'transactions.id', '=', 'compiled_documents.transaction_id')
                ->orderBy('transactions.registration_number', $direction);
        } else {
            $archiveQuery->orderBy('compiled_documents.'.$sort, $direction);
        }

        $archives = $archiveQuery
            ->paginate(10)
            ->withQueryString();

        return view('invoice-verification.archive.index', compact('archives', 'sort', 'direction', 'search'));
    }
}
