<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use App\Modules\InvoiceVerification\Domain\Models\NumberingRegister;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;
use BackedEnum;
use Illuminate\Database\Eloquent\Collection;
use ZipArchive;

class NumberingRegisterExportService
{
    private const PPA_SPU_HEADERS = [
        'No',
        'Jenis Dok',
        'Nomor Dokumen',
        'Nama Vendor',
        'Soft Copy',
        'Hard Copy',
        'Deskripsi Divisi',
        'Kode Divisi',
        'Tanggal Dokumen',
        'Tanggal Terima (Soft Copy)',
        'Tanggal Terima (Hard copy)',
        'Tanggal Jatuh Tempo',
        'Nomor Invoice',
        'Tanggal Invoice',
        'Bank',
        'Kode Bank',
        'No Rekening',
        'Nama Alias',
        'Sales Tax Group',
        'NPWP',
        'No Faktur Pajak',
        'Tanggal Faktur Pajak',
        'No Memo Permohonan',
        'Disetujui Oleh',
        'No Kontrak/PO/SPK/BAKN/FORMULIR',
        'Underlying',
        'Nilai Kontrak BEFORE TAX (Rp)',
        'Disetujui Oleh',
        'Mata Uang',
        'Nilai Kurs (Rp)',
        'Nilai Pengajuan SPU (Rp)',
        'Nilai Invoice (Rp)',
        'PPN (Rp)',
        'PPh (Rp)',
        'Disc/Denda/Materai (Rp)',
        'Total After Tax (Rp)',
        'Uraian Transaksi',
        'Jenis Cost',
        'PIC Pekerjaan',
        'BAPP',
        'BAST',
        'Tanggal Terima (Lengkap)',
        'Status Dokumen Transaksi',
        'Tanggal Ekspedisi Pembayaran',
        'Status Pembayaran (1)',
        'Rekening Sumber (1)',
        'Tanggal Pembayaran (1)',
        'Status Pembayaran (2)',
        'Rekening Sumber (2)',
        'Tanggal Pos Silang (2)',
        'Periode Pembayaran',
        'Nomor Vendor Payment Journal',
        'Pertanggung Jawaban SPU',
        'Tanggal SPUK',
        'Umur SPU',
        'Umur Utang',
        '0-40 Hari',
        '41-60 Hari',
        '61-90 Hari',
        '>91 Hari',
        'Status Transaksi',
        'Periode Anggaran',
        'Nilai Pertanggungjawaban',
        'Periode Pencatatan',
        'Kategori',
        'Nomor Jurnal ERP',
        'Advis Status',
        'Tanggal Terima (Vendor)',
        'Nomor GR',
        'Proforma Number',
    ];

    private const SPUK_HEADERS = [
        'No',
        'Nomor SPU',
        'Nomor SPUK',
        'Deskripsi Divisi',
        'Kode Div.',
        'Keterangan',
        'Soft Copy',
        'Hard Copy',
        'Tanggal SPU',
        'Tanggal SPUK',
        'Tgl Terima e-mail',
        'Tgl Terima Hardcopy',
        'Nilai Pengajuan (Rp)',
        'Nilai Realisasi (Rp)',
        'Nilai Kelebihan/Kekurangan (Rp)',
        'PIC',
        'Tgl Pengembalian Kelebihan',
        'Nominal (Rp)',
        'Nama Bank',
        'Nomor Rekening',
        'Tgl Ekspedisi Pembayaran',
        'Tgl Pencairan Kekurangan',
        'Status SPUK',
        'Notes SPUK',
        'Umur SPU',
        'Accounting Date',
        'Period',
        'VP-RSPU',
    ];

    private const KK_HEADERS = [
        'No',
        'No KK',
        'Kode Divisi',
        'Nama Vendor',
        'Deskripsi Divisi',
        'Uraian Transaksi',
        'SOFT COPY',
        'HARD COPY',
        'Tanggal Dokumen',
        'Tanggal Terima (Soft Copy)',
        'Tanggal Terima (Hard Copy)',
        'Tanggal Jatuh Tempo',
        'Bank',
        'Kode Bank',
        'No Rekening',
        'Nama Alias',
        'Plafon Petty Cash (Rp)',
        'Sisa Petty Cash (Rp)',
        'Nilai Top Up (Rp)',
        'Status Dokumen Transaksi',
        'Tanggal Ekspedisi',
        'Status of Payment (1)',
        'Rekening Sumber (1)',
        'Date of Payment (1)',
        'Jumlah Transfer (1)',
        'Status of Payment (2)',
        'Rekening Sumber (2)',
        'Date of Payment (2)',
        'Jumlah Transfer (2)',
        'Periode Pembayaran',
        'Umur Utang',
        '0-30 Hari',
        '31-60 Hari',
        '61-90 Hari',
        '>91 Hari',
        'Status Transaksi',
        'Nomor Payment Journal',
        'Advis Status',
    ];

    public function export(Collection $registers, string $path): void
    {
        $sheets = [
            'PPA & SPU' => $this->buildPpaSpuRows($registers),
            'SPUK' => $this->buildSpukRows($registers),
            'KK' => $this->buildKkRows($registers),
        ];

        $this->writeWorkbook($sheets, $path);
    }

    private function buildPpaSpuRows(Collection $registers): array
    {
        $rows = $this->baseRows('PPA & SPU', self::PPA_SPU_HEADERS);
        $index = 1;

        foreach ($registers as $register) {
            $type = $this->typeCode($register);

            if (! in_array($type, [TransactionTypeCode::PPA->value, TransactionTypeCode::PPA_NON_CONTRACT->value, TransactionTypeCode::SPU->value], true)) {
                continue;
            }

            $transaction = $register->transaction;
            $documentNumber = $transaction?->registration_number ?: $register->register_number;
            $invoiceDocument = $this->documentInfo($register, 'PPA_INVOICE');
            $taxDocument = $this->documentInfo($register, 'PPA_FAKTUR_PAJAK');
            $bapp = $this->documentInfo($register, 'PPA_BAPP');
            $bast = $this->documentInfo($register, 'PPA_BAST');
            $invoiceValue = (float) ($register->invoice_value ?? 0);
            $ppnValue = (float) ($register->ppn_value ?? 0);
            $pphValue = (float) ($register->pph_value ?? 0);

            $rows[] = [
                $index++,
                $this->documentPrefix($documentNumber),
                $documentNumber,
                $register->vendor_name,
                $register->received_date ? 'P' : '',
                '',
                $transaction?->division?->name ?? '',
                $transaction?->division?->code ?? '',
                $register->generated_at?->format('Y-m-d'),
                $register->received_date?->format('Y-m-d'),
                '',
                $register->received_date?->copy()->addDays(30)->format('Y-m-d'),
                $register->invoice_number,
                $register->invoice_date?->format('Y-m-d'),
                $register->bank_name,
                '',
                $register->account_number,
                $register->account_name,
                '',
                $transaction?->vendor?->npwp,
                $taxDocument['document_number'] ?? '',
                $taxDocument['document_date'] ?? '',
                $register->memo_number,
                '',
                $register->contract_number,
                '',
                $this->numberOrBlank($register->contract_value),
                '',
                'IDR',
                '',
                $this->numberOrBlank($transaction?->spu_amount),
                $this->numberOrBlank($invoiceValue),
                $this->numberOrBlank($ppnValue),
                $this->numberOrBlank($pphValue),
                '',
                $this->numberOrBlank($invoiceValue + $ppnValue - $pphValue),
                $register->description,
                '',
                $transaction?->owner?->name,
                $bapp['document_number'] ?? '',
                $bast['document_number'] ?? '',
                $register->received_date?->format('Y-m-d'),
                $this->documentStatus($register),
                $transaction?->scheduled_payment_at?->format('Y-m-d'),
                $this->paymentStatus($register),
                '',
                $transaction?->paid_at?->format('Y-m-d'),
                '',
                '',
                '',
                $transaction?->scheduled_payment_at?->format('m y'),
                '',
                '',
                '',
                '',
                $this->ageDays($register),
                $this->ageBucket($register, 0, 40),
                $this->ageBucket($register, 41, 60),
                $this->ageBucket($register, 61, 90),
                $this->ageBucket($register, 91, null),
                $register->transaction?->status?->label(),
                $register->generated_at?->format('Y'),
                '',
                $register->generated_at?->format('m Y'),
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ];
        }

        return $rows;
    }

    private function buildSpukRows(Collection $registers): array
    {
        $rows = $this->baseRows('SPUK', self::SPUK_HEADERS);
        $index = 1;

        foreach ($registers as $register) {
            if ($this->typeCode($register) !== TransactionTypeCode::SPUK->value) {
                continue;
            }

            $transaction = $register->transaction;

            $rows[] = [
                $index++,
                $transaction?->parentSpuTransaction?->registration_number,
                $transaction?->registration_number ?: $register->register_number,
                $transaction?->division?->name,
                $transaction?->division?->code,
                $register->description,
                $register->received_date ? 'P' : '',
                '',
                $transaction?->parentSpuTransaction?->created_at?->format('Y-m-d'),
                $register->generated_at?->format('Y-m-d'),
                $register->received_date?->format('Y-m-d'),
                '',
                $this->numberOrBlank($transaction?->spu_amount),
                $this->numberOrBlank($transaction?->accountability_amount),
                $this->numberOrBlank($transaction?->remaining_amount),
                $transaction?->owner?->name,
                '',
                $this->numberOrBlank($transaction?->remaining_amount),
                $register->bank_name,
                $register->account_number,
                $transaction?->scheduled_payment_at?->format('Y-m-d'),
                $transaction?->paid_at?->format('Y-m-d'),
                $transaction?->status?->label(),
                '',
                $this->ageDays($register),
                $register->generated_at?->format('Y-m-d'),
                $register->generated_at?->format('m y'),
                '',
            ];
        }

        return $rows;
    }

    private function buildKkRows(Collection $registers): array
    {
        $rows = $this->baseRows('PETTY CASH', self::KK_HEADERS);
        $index = 1;

        foreach ($registers as $register) {
            if ($this->typeCode($register) !== TransactionTypeCode::KAS_KECIL->value) {
                continue;
            }

            $transaction = $register->transaction;
            $topUp = (float) ($transaction?->petty_cash_top_up_amount ?? $register->invoice_value ?? 0);

            $rows[] = [
                $index++,
                $transaction?->registration_number ?: $register->register_number,
                $transaction?->division?->code,
                $register->vendor_name,
                $transaction?->division?->name,
                $register->description,
                $register->received_date ? 'P' : '',
                '',
                $register->generated_at?->format('Y-m-d'),
                $register->received_date?->format('Y-m-d'),
                '',
                $register->received_date?->copy()->addDays(14)->format('Y-m-d'),
                $register->bank_name,
                '',
                $register->account_number,
                $register->account_name,
                $this->numberOrBlank($transaction?->petty_cash_ceiling_snapshot),
                $this->numberOrBlank($transaction?->petty_cash_remaining_amount),
                $this->numberOrBlank($topUp),
                $this->documentStatus($register),
                $transaction?->scheduled_payment_at?->format('Y-m-d'),
                $this->paymentStatus($register),
                '',
                $transaction?->paid_at?->format('Y-m-d'),
                $this->numberOrBlank($topUp),
                '',
                '',
                '',
                '',
                $transaction?->scheduled_payment_at?->format('m y'),
                $this->ageDays($register),
                $this->ageBucket($register, 0, 30),
                $this->ageBucket($register, 31, 60),
                $this->ageBucket($register, 61, 90),
                $this->ageBucket($register, 91, null),
                $transaction?->paid_at ? 'Lunas' : 'Belum Lunas',
                '',
                '',
            ];
        }

        return $rows;
    }

    private function baseRows(string $title, array $headers): array
    {
        return [
            [$title],
            ['Tahun Anggaran', now()->year],
            [],
            $headers,
        ];
    }

    private function documentInfo(NumberingRegister $register, string $code): array
    {
        $document = $register->transaction?->latestDocuments
            ?->first(fn ($item) => $this->enumValue($item->documentType?->code) === $code);

        return $document?->document_information_json ?? [];
    }

    private function documentStatus(NumberingRegister $register): string
    {
        $transaction = $register->transaction;

        if (! $transaction?->latestDocuments || $transaction->latestDocuments->isEmpty()) {
            return 'BELUM LENGKAP';
        }

        return $transaction->latestDocuments
            ->contains(fn (TransactionDocument $document) => in_array($this->enumValue($document->status), ['UNDER_REVIEW', 'REVISION_REQUIRED'], true))
            ? 'BELUM LENGKAP'
            : 'LENGKAP';
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    private function paymentStatus(NumberingRegister $register): string
    {
        return $register->transaction?->paid_at ? 'LUNAS' : 'ON PROCESS';
    }

    private function typeCode(NumberingRegister $register): ?string
    {
        return $register->transaction?->transactionType?->code?->value;
    }

    private function documentPrefix(?string $documentNumber): string
    {
        return strtoupper(substr((string) $documentNumber, 0, 3));
    }

    private function ageDays(NumberingRegister $register): int|string
    {
        if (! $register->received_date) {
            return '';
        }

        $end = $register->transaction?->paid_at ?: now();

        return (int) $register->received_date->diffInDays($end);
    }

    private function ageBucket(NumberingRegister $register, int $min, ?int $max): int|float|string
    {
        $age = $this->ageDays($register);

        if ($age === '') {
            return '';
        }

        if ($age < $min || ($max !== null && $age > $max)) {
            return 0;
        }

        return $this->numberOrBlank($register->invoice_value);
    }

    private function numberOrBlank(mixed $value): int|float|string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (float) $value;
    }

    private function writeWorkbook(array $sheets, string $path): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml(array_keys($sheets)));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml(count($sheets)));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        $index = 1;
        foreach ($sheets as $rows) {
            $zip->addFromString("xl/worksheets/sheet{$index}.xml", $this->sheetXml($rows));
            $index++;
        }

        $zip->close();
    }

    private function contentTypesXml(int $sheetCount): string
    {
        $sheets = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $sheets .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$sheets
            .'</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(array $sheetNames): string
    {
        $sheets = '';
        foreach ($sheetNames as $index => $name) {
            $sheetId = $index + 1;
            $sheets .= '<sheet name="'.$this->xml($name).'" sheetId="'.$sheetId.'" r:id="rId'.$sheetId.'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheets.'</sheets>'
            .'</workbook>';
    }

    private function workbookRelsXml(int $sheetCount): string
    {
        $rels = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $rels .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }

        $rels .= '<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$rels
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function sheetXml(array $rows): string
    {
        $maxCols = max(array_map(fn ($row) => count($row), $rows));
        $cols = '';
        for ($i = 1; $i <= $maxCols; $i++) {
            $cols .= '<col min="'.$i.'" max="'.$i.'" width="'.($i <= 4 ? 18 : 14).'" customWidth="1"/>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols>'.$cols.'</cols><sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $r = $rowIndex + 1;
            $xml .= '<row r="'.$r.'">';
            foreach ($row as $columnIndex => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $cell = $this->cellAddress($columnIndex + 1, $r);
                $style = in_array($r, [1, 2, 4], true) ? ' s="1"' : '';

                if (is_int($value) || is_float($value)) {
                    $xml .= '<c r="'.$cell.'"'.$style.'><v>'.$value.'</v></c>';
                } else {
                    $xml .= '<c r="'.$cell.'" t="inlineStr"'.$style.'><is><t>'.$this->xml((string) $value).'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private function cellAddress(int $column, int $row): string
    {
        $letters = '';
        while ($column > 0) {
            $column--;
            $letters = chr(65 + ($column % 26)).$letters;
            $column = intdiv($column, 26);
        }

        return $letters.$row;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
