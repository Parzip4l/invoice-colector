<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Http\Requests\PpaVerificationDecisionRequest;
use App\Modules\InvoiceVerification\Http\Requests\SavePpaVerificationSheetRequest;
use App\Modules\InvoiceVerification\Services\PpaVerificationSheetService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PpaVerificationSheetController extends Controller
{
    public function __construct(
        protected PpaVerificationSheetService $ppaVerificationSheetService,
    ) {
    }

    public function edit(Transaction $transaction)
    {
        $this->authorize('view', $transaction);

        $sheet = $this->ppaVerificationSheetService->getOrCreate($transaction, request()->user());

        return view('invoice-verification.ppa-verification-sheets.edit', compact('transaction', 'sheet'));
    }

    public function preview(Transaction $transaction): StreamedResponse
    {
        $this->authorize('view', $transaction);

        $sheet = $transaction->ppaVerificationSheet;

        abort_unless($sheet && $sheet->file_disk && $sheet->file_path, 404);
        abort_unless(Storage::disk($sheet->file_disk)->exists($sheet->file_path), 404);

        $fileName = $sheet->file_name ?: basename($sheet->file_path);
        $mimeType = $sheet->mime_type ?: Storage::disk($sheet->file_disk)->mimeType($sheet->file_path);
        if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) === 'pdf') {
            $mimeType = 'application/pdf';
        }

        return Storage::disk($sheet->file_disk)->response(
            $sheet->file_path,
            $fileName,
            [
                'Content-Type' => $mimeType ?: 'application/octet-stream',
            ],
            'inline',
        );
    }

    public function update(SavePpaVerificationSheetRequest $request, Transaction $transaction)
    {
        $this->ppaVerificationSheetService->saveChecklist($transaction, $request->user(), $request->validated('items'));

        return redirect()
            ->route('invoice-verification.ppa-verification-sheets.edit', $transaction)
            ->with('success', 'Checklist PPA berhasil disimpan.');
    }

    public function submit(Transaction $transaction)
    {
        $this->authorize('view', $transaction);
        $this->ppaVerificationSheetService->submit($transaction, request()->user());

        return redirect()
            ->route('invoice-verification.transactions.show', $transaction)
            ->with('success', 'Lembar verifikasi PPA berhasil diajukan untuk approval.');
    }

    public function decision(PpaVerificationDecisionRequest $request, Transaction $transaction)
    {
        $this->ppaVerificationSheetService->approve(
            $transaction,
            $request->user(),
            $request->validated('decision') === 'APPROVED',
            $request->validated('notes'),
        );

        return redirect()
            ->route('invoice-verification.transactions.show', $transaction)
            ->with('success', 'Keputusan lembar verifikasi PPA berhasil disimpan.');
    }
}
