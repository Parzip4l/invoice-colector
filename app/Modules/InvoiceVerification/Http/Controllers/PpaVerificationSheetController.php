<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Http\Requests\SavePpaVerificationSheetRequest;
use App\Modules\InvoiceVerification\Services\Implementations\FunctionalPpaVerificationSheetPdfGenerator;
use App\Modules\InvoiceVerification\Services\PpaVerificationSheetService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PpaVerificationSheetController extends Controller
{
    public function __construct(
        protected PpaVerificationSheetService $ppaVerificationSheetService,
        protected FunctionalPpaVerificationSheetPdfGenerator $pdfGenerator,
    ) {}

    public function edit(Transaction $transaction)
    {
        $this->authorize('view', $transaction);

        $sheet = $this->ppaVerificationSheetService->getOrCreate($transaction, request()->user());

        if ($transaction->isPpaNonContract()) {
            $checklistCategories = $this->pdfGenerator->buildChecklistCategories($sheet);

            return view('invoice-verification.ppa-verification-sheets.edit', compact('transaction', 'sheet', 'checklistCategories'));
        }

        return view('invoice-verification.ppa-verification-sheets.edit', compact('transaction', 'sheet'));
    }

    public function preview(Transaction $transaction): StreamedResponse
    {
        $this->authorize('view', $transaction);

        $sheet = $transaction->ppaVerificationSheet()->first() ?? $transaction->ppaVerificationSheet;
        $latestApprovalUpdate = $transaction->approvalTransactions()->max('updated_at');

        $needsRegeneration = ! $sheet
            || ! $sheet->file_path
            || ! Storage::disk($sheet->file_disk)->exists($sheet->file_path)
            || request()->boolean('refresh')
            || ($latestApprovalUpdate && $sheet->updated_at && $sheet->updated_at->lt($latestApprovalUpdate));

        if ($needsRegeneration) {
            $sheet = $this->ppaVerificationSheetService->regeneratePdf($transaction);
        }

        abort_unless($sheet && $sheet->file_disk && $sheet->file_path, 404);
        abort_unless(Storage::disk($sheet->file_disk)->exists($sheet->file_path), 404);

        $fileName = $sheet->file_name ?: basename($sheet->file_path);
        $mimeType = 'application/pdf';

        return Storage::disk($sheet->file_disk)->response(
            $sheet->file_path,
            $fileName,
            [
                'Content-Type' => $mimeType,
            ],
            'inline',
        );
    }

    public function download(Transaction $transaction): StreamedResponse
    {
        $this->authorize('view', $transaction);

        $sheet = $transaction->ppaVerificationSheet()->first() ?? $transaction->ppaVerificationSheet;
        $latestApprovalUpdate = $transaction->approvalTransactions()->max('updated_at');

        $needsRegeneration = ! $sheet
            || ! $sheet->file_path
            || ! Storage::disk($sheet->file_disk)->exists($sheet->file_path)
            || request()->boolean('refresh')
            || ($latestApprovalUpdate && $sheet->updated_at && $sheet->updated_at->lt($latestApprovalUpdate));

        if ($needsRegeneration) {
            $sheet = $this->ppaVerificationSheetService->regeneratePdf($transaction);
        }

        abort_unless($sheet && $sheet->file_disk && $sheet->file_path, 404);
        abort_unless(Storage::disk($sheet->file_disk)->exists($sheet->file_path), 404);

        $fileName = $sheet->file_name ?: ('ppa-verification-sheet-'.$transaction->registration_number.'.pdf');

        return Storage::disk($sheet->file_disk)->download(
            $sheet->file_path,
            $fileName,
            [
                'Content-Type' => 'application/pdf',
            ],
        );
    }

    public function update(SavePpaVerificationSheetRequest $request, Transaction $transaction)
    {
        if ($transaction->isPpaNonContract()) {
            $this->ppaVerificationSheetService->savePpaNonContractSheet($transaction, $request->user(), $request->validated());

            if ($request->input('action') === 'submit') {
                $this->ppaVerificationSheetService->submitPpaNonContract($transaction, $request->user());

                return redirect()
                    ->route('invoice-verification.transactions.show', $transaction)
                    ->with('success', 'Formulir Pengajuan Penggunaan Anggaran & Lembar Checklist berhasil disimpan dan diajukan untuk approval Kepala Departemen & Kepala Divisi.');
            }

            return redirect()
                ->route('invoice-verification.transactions.ppa-verification-sheets.edit', $transaction)
                ->with('success', 'Formulir Pengajuan Penggunaan Anggaran & Lembar Checklist berhasil disimpan.');
        }

        $this->ppaVerificationSheetService->saveChecklist($transaction, $request->user(), $request->validated('items'));

        return redirect()
            ->route('invoice-verification.transactions.ppa-verification-sheets.edit', $transaction)
            ->with('success', 'Checklist PPA berhasil disimpan.');
    }

    public function submit(Transaction $transaction)
    {
        $sheet = $this->ppaVerificationSheetService->getOrCreate($transaction, request()->user());
        $this->authorize('update', $sheet);

        if ($transaction->isPpaNonContract()) {
            $this->ppaVerificationSheetService->submitPpaNonContract($transaction, request()->user());

            return redirect()
                ->route('invoice-verification.transactions.show', $transaction)
                ->with('success', 'Pengajuan Penggunaan Anggaran berhasil disubmit untuk approval Kepala Departemen & Kepala Divisi.');
        }

        $this->ppaVerificationSheetService->submit($transaction, request()->user());

        return redirect()
            ->route('invoice-verification.transactions.show', $transaction)
            ->with('success', 'Lembar verifikasi PPA berhasil disubmit.');
    }
}
