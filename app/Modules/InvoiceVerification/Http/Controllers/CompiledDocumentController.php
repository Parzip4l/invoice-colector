<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Models\CompiledDocument;

class CompiledDocumentController extends Controller
{
    public function index()
    {
        $compiledDocuments = CompiledDocument::query()
            ->with('transaction.transactionType')
            ->latest('compiled_at')
            ->paginate(10);

        return view('invoice-verification.compiled-documents.index', compact('compiledDocuments'));
    }

    public function show(CompiledDocument $compiledDocument)
    {
        $compiledDocument->load(['transaction', 'items', 'compiler', 'archivedBy']);

        return view('invoice-verification.compiled-documents.show', compact('compiledDocument'));
    }
}
