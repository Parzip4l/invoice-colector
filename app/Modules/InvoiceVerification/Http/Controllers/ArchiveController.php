<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Models\CompiledDocument;

class ArchiveController extends Controller
{
    public function index()
    {
        $archives = CompiledDocument::query()
            ->with('transaction.transactionType')
            ->whereNotNull('archived_at')
            ->latest('archived_at')
            ->paginate(10);

        return view('invoice-verification.archive.index', compact('archives'));
    }
}
