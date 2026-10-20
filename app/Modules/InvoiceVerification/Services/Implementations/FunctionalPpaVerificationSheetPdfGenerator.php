<?php

namespace App\Modules\InvoiceVerification\Services\Implementations;

use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet;
use App\Modules\InvoiceVerification\Services\Contracts\PpaVerificationSheetPdfGenerator;
use App\Modules\InvoiceVerification\Services\PdfRenderingService;
use App\Modules\InvoiceVerification\Services\SignatureQrService;
use App\Modules\InvoiceVerification\Support\NumberToWords;

class FunctionalPpaVerificationSheetPdfGenerator implements PpaVerificationSheetPdfGenerator
{
    public function __construct(
        protected PdfRenderingService $pdfRenderingService,
        protected SignatureQrService $signatureQrService,
    ) {
    }

    public function generate(PpaVerificationSheet $sheet): array
    {
        $disk = config('invoice_verification.storage.documents_disk');
        $sheet->loadMissing([
            'transaction.transactionType',
            'transaction.vendor',
            'transaction.division',
            'transaction.department.users',
            'transaction.invoiceMetadata',
            'transaction.latestDocuments.documentType',
            'transaction.approvalTransactions.approvalFlow',
            'transaction.approvalTransactions.approver',
            'items.documentType',
            'filledBy',
            'approvedBy',
        ]);

        $transaction = $sheet->transaction;
        $fileName = 'ppa-verification-sheet-'.$transaction->registration_number.'.pdf';
        $path = 'transactions/'.$sheet->transaction_id.'/generated/'.$fileName;

        if ($transaction->isPpaNonContract()) {
            $data = $this->buildPpaNonContractData($sheet);
            $view = 'invoice-verification.pdf.ppa-non-contract-sheet';
        } else {
            $data = [
                'sheet' => $sheet,
                'generatedAt' => now(),
            ];
            $view = 'invoice-verification.pdf.ppa-verification-sheet';
        }

        $this->pdfRenderingService->renderToDisk(
            $view,
            $data,
            $disk,
            $path,
        );

        return [
            'file_name' => $fileName,
            'file_disk' => $disk,
            'file_path' => $path,
        ];
    }

    public function buildPpaNonContractData(PpaVerificationSheet $sheet): array
    {
        $transaction = $sheet->transaction;
        $metadata = $transaction->invoiceMetadata;

        $logoPath = public_path('images/lrt-jakarta-logo.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : null;

        $budgetItems = (array) ($sheet->budget_items_json ?: []);
        if (empty($budgetItems)) {
            $defaultRealization = (float) (($metadata?->invoice_value ?? 0) + ($metadata?->ppn_value ?? 0));
            $budgetItems = [
                [
                    'kode_rab' => '',
                    'nama_kegiatan' => $metadata?->description ?? $transaction->description ?? 'Konsumsi Kegiatan',
                    'nilai_realisasi' => $defaultRealization > 0 ? $defaultRealization : 0,
                ],
            ];
        }

        $totalRealization = (float) collect($budgetItems)->sum(fn ($item) => (float) ($item['nilai_realisasi'] ?? 0));
        if ($totalRealization <= 0 && $metadata) {
            $totalRealization = (float) ($metadata->invoice_value + $metadata->ppn_value);
        }

        $contractValue = (float) ($metadata?->contract_value ?? $transaction->contract_value ?? $totalRealization);
        $advancePaymentValue = (float) ($sheet->advance_payment_value ?? 0);
        $invoiceValue = (float) ($metadata?->invoice_value ?? $totalRealization);
        $exchangeRate = (float) ($sheet->exchange_rate_value ?? 1.00);
        $terbilang = NumberToWords::terbilang($totalRealization);

        $kadepApproval = $transaction->approvalTransactions
            ->first(fn ($item) => (int) $item->approvalFlow?->step_no === 1);
        $kadivApproval = $transaction->approvalTransactions
            ->first(fn ($item) => (int) $item->approvalFlow?->step_no === 2);

        $kadepUserFallback = $transaction->department?->users
            ?->first(fn ($u) => ($u->role_code?->value ?? $u->role_code) === RoleCode::KEPALA_DEPARTEMEN->value);
        $kadivUserFallback = $transaction->division?->users
            ?->first(fn ($u) => ($u->role_code?->value ?? $u->role_code) === RoleCode::KEPALA_DIVISI->value);

        $kadepName = $kadepApproval?->approver?->name
            ?? data_get($kadepApproval?->portal_qr_payload, 'signer.name')
            ?? $kadepUserFallback?->name
            ?? 'Manager Bagian';

        $kadepNik = $kadepApproval?->approver?->employee_number
            ?? $kadepApproval?->approver?->employee_id
            ?? data_get($kadepApproval?->portal_qr_payload, 'signer.employee_number')
            ?? data_get($kadepApproval?->portal_qr_payload, 'signer.employee_id')
            ?? $kadepUserFallback?->employee_number
            ?? $kadepUserFallback?->employee_id
            ?? $kadepUserFallback?->nik
            ?? '-';

        $kadepTitle = $kadepApproval?->approvalFlow?->step_name
            ?? ('Manager '.($transaction->department?->name ?? 'Bagian'));

        $kadivName = $kadivApproval?->approver?->name
            ?? data_get($kadivApproval?->portal_qr_payload, 'signer.name')
            ?? $kadivUserFallback?->name
            ?? 'Kepala Divisi';

        $kadivNik = $kadivApproval?->approver?->employee_number
            ?? $kadivApproval?->approver?->employee_id
            ?? data_get($kadivApproval?->portal_qr_payload, 'signer.employee_number')
            ?? data_get($kadivApproval?->portal_qr_payload, 'signer.employee_id')
            ?? $kadivUserFallback?->employee_number
            ?? $kadivUserFallback?->employee_id
            ?? $kadivUserFallback?->nik
            ?? '-';

        $kadivTitle = $kadivApproval?->approvalFlow?->step_name
            ?? ('Kepala Divisi '.($transaction->division?->name ?? ''));

        $accountingName = $sheet->accounting_signer_name ?: 'Budi Sarjono';

        $kadepQrBase64 = null;
        if ($kadepApproval && $kadepApproval->status === ApprovalStatus::APPROVED) {
            $qrContent = $kadepApproval->portal_signature_url
                ?: ($kadepApproval->portal_qr_url ?: route('invoice-verification.transactions.show', $transaction));
            $kadepQrBase64 = $this->signatureQrService->generateBase64Svg($qrContent);
        }

        $kadivQrBase64 = null;
        if ($kadivApproval && $kadivApproval->status === ApprovalStatus::APPROVED) {
            $qrContent = $kadivApproval->portal_signature_url
                ?: ($kadivApproval->portal_qr_url ?: route('invoice-verification.transactions.show', $transaction));
            $kadivQrBase64 = $this->signatureQrService->generateBase64Svg($qrContent);
        }

        $accountingQrBase64 = null;
        if ($transaction->accountingVerification?->status?->value === 'ACCEPTED'
            || in_array($transaction->status?->value, ['COMPLETED', 'PAID', 'RECEIVED'], true)) {
            $accountingQrBase64 = $this->signatureQrService->generateBase64Svg(route('invoice-verification.transactions.show', $transaction));
        }

        $checklistCategories = $this->buildChecklistCategories($sheet);

        $actionDate = $kadivApproval?->action_at ?? $kadepApproval?->action_at ?? $sheet->submitted_at ?? now();
        $signatureDate = $actionDate->translatedFormat('d F Y');

        return [
            'sheet' => $sheet,
            'transaction' => $transaction,
            'logoBase64' => $logoBase64,
            'budgetYear' => now()->year,
            'budgetItems' => $budgetItems,
            'totalRealization' => $totalRealization,
            'contractValue' => $contractValue,
            'advancePaymentValue' => $advancePaymentValue,
            'invoiceValue' => $invoiceValue,
            'exchangeRate' => $exchangeRate,
            'terbilang' => $terbilang,
            'signatureDate' => $signatureDate,
            'kadepTitle' => $kadepTitle,
            'kadepName' => $kadepName,
            'kadepNik' => $kadepNik,
            'kadepQrBase64' => $kadepQrBase64,
            'kadivTitle' => $kadivTitle,
            'kadivName' => $kadivName,
            'kadivNik' => $kadivNik,
            'kadivQrBase64' => $kadivQrBase64,
            'accountingName' => $accountingName,
            'accountingQrBase64' => $accountingQrBase64,
            'checklistCategories' => $checklistCategories,
        ];
    }

    public function buildChecklistCategories(PpaVerificationSheet $sheet): array
    {
        $stored = (array) ($sheet->checklist_items_json ?: []);
        $transaction = $sheet->transaction;
        $uploadedDocs = $transaction->latestDocuments->keyBy(fn ($doc) => $doc->documentType?->code?->value ?? $doc->documentType?->code);

        $structure = [
            [
                'roman' => 'I',
                'title' => 'Persetujuan Prinsip',
                'items' => [
                    ['key' => 'I_1', 'no' => '1', 'name' => 'Rencana Kerja dan Anggaran Perusahaan', 'doc_code' => null],
                    ['key' => 'I_2', 'no' => '2', 'name' => 'Memo Persetujuan', 'doc_code' => 'PNK_MEMO_PERMOHONAN'],
                ],
            ],
            [
                'roman' => 'II',
                'title' => 'Kontrak',
                'items' => [
                    ['key' => 'II_1', 'no' => '1', 'name' => 'Perjanjian', 'doc_code' => null],
                    ['key' => 'II_2', 'no' => '2', 'name' => 'Surat Perintah Mulai Kerja', 'doc_code' => null],
                    ['key' => 'II_3', 'no' => '3', 'name' => 'Surat Pemesanan Barang', 'doc_code' => null],
                ],
            ],
            [
                'roman' => 'III',
                'title' => 'Remunerasi, Pelatihan, dan Biaya-Biaya SDM',
                'items' => [
                    ['key' => 'III_1', 'no' => '1', 'name' => 'SK Direksi', 'doc_code' => null],
                    ['key' => 'III_2', 'no' => '2', 'name' => 'Rekapitulasi Perhitungan', 'doc_code' => null],
                ],
            ],
            [
                'roman' => 'IV',
                'title' => 'Dokumen Pendukung',
                'items' => [
                    ['key' => 'IV_1', 'no' => '1', 'name' => 'Tagihan/Invoice', 'doc_code' => 'PNK_INVOICE', 'default_required' => true],
                    ['key' => 'IV_2', 'no' => '2', 'name' => 'Kwitansi', 'doc_code' => 'PNK_KWITANSI', 'default_required' => true],
                    ['key' => 'IV_3', 'no' => '3', 'name' => 'Faktur Pajak', 'doc_code' => 'PNK_FAKTUR_PAJAK', 'default_required' => true],
                    ['key' => 'IV_4', 'no' => '4', 'name' => 'Tanda Terima Barang', 'doc_code' => 'PNK_LEMBAR_TANDA_TERIMA', 'default_required' => true],
                    ['key' => 'IV_5', 'no' => '5', 'name' => 'Laporan Pekerjaan/Kegiatan', 'doc_code' => 'PNK_LAPORAN_PEKERJAAN', 'default_required' => true],
                    ['key' => 'IV_6', 'no' => '6', 'name' => 'Berita Acara Serah Terima Pekerjaan', 'doc_code' => null],
                    ['key' => 'IV_7', 'no' => '7', 'name' => 'Dokumen Serah Terima antara Vendor dan User atas Tagihan Pekerjaan', 'doc_code' => null, 'default_required' => true],
                    ['key' => 'IV_8', 'no' => '8', 'name' => 'Dokumen Lainnya Yang Diperlukan', 'doc_code' => 'PNK_DOKUMEN_PENDUKUNG'],
                ],
            ],
            [
                'roman' => 'V',
                'title' => 'Kelengkapan ERP',
                'items' => [
                    ['key' => 'V_1_a', 'no' => 'a.', 'name' => 'Purchase Requisition', 'is_sub' => true, 'doc_code' => null],
                    ['key' => 'V_1_b', 'no' => 'b.', 'name' => 'Purchase Order', 'is_sub' => true, 'doc_code' => null],
                    ['key' => 'V_1_c', 'no' => 'c.', 'name' => 'Goods Receive', 'is_sub' => true, 'doc_code' => null],
                    ['key' => 'V_2', 'no' => '2', 'name' => 'Invoice Journal', 'doc_code' => null],
                    ['key' => 'V_3', 'no' => '3', 'name' => 'Cash Advance (SPU)', 'doc_code' => null],
                    ['key' => 'V_4', 'no' => '4', 'name' => 'Dokumen Soft Copy', 'doc_code' => null],
                ],
            ],
        ];

        return collect($structure)->map(function ($cat) use ($stored, $uploadedDocs) {
            $cat['items'] = collect($cat['items'])->map(function ($item) use ($stored, $uploadedDocs) {
                $key = $item['key'];
                $docCode = $item['doc_code'] ?? null;
                $doc = $docCode ? $uploadedDocs->get($docCode) : null;
                $docNumber = data_get($doc?->document_information_json, 'document_number');

                $storedItem = $stored[$key] ?? null;

                $isRequired = $storedItem !== null
                    ? (bool) ($storedItem['is_required'] ?? false)
                    : (bool) ($item['default_required'] ?? ($doc !== null));

                $isActual = $storedItem !== null
                    ? (bool) ($storedItem['is_actual'] ?? false)
                    : ($doc !== null);

                $notes = $storedItem['notes'] ?? ($docNumber ?: ($item['default_notes'] ?? ''));

                return [
                    'key' => $key,
                    'no' => $item['no'],
                    'name' => $item['name'],
                    'is_sub' => $item['is_sub'] ?? false,
                    'is_required' => $isRequired,
                    'is_actual' => $isActual,
                    'notes' => $notes,
                    'verification_status' => $storedItem['verification_status'] ?? '',
                ];
            })->all();

            return $cat;
        })->all();
    }
}
