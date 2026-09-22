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
    private const PPA_HEADERS = [
        'Nomor Transaksi',
        'Nama Vendor',
        'Softcopy',
        'Hardcopy',
        'Tanggal Submit Transaksi',
        'Due Date',
        'Nomor Invoice',
        'Tanggal Invoice',
        'Nama Bank',
        'Nomor Rekening',
        'Nama Rekening',
        'Nomor Faktur Pajak',
        'Tanggal Faktur Pajak',
        'Nomor Memo',
        'Nomor Kontrak',
        'Nilai Kontrak',
        'Nilai Invoice',
        'Nilai PPN',
        'Nilai PPh',
        'Discount/Denda/Materai',
        'Total After Tax',
        'Uraian Transaksi',
        'Nomor GR',
        'Jenis Jurnal',
        'Status Pembayaran',
        'Tanggal Ekspedisi',
        'Tanggal Pembayaran',
    ];

    private const SPU_HEADERS = [
        'Nomor Transaksi',
        'Nama Divisi',
        'Softcopy',
        'Hardcopy',
        'Tanggal Submit Transaksi',
        'Nama Bank',
        'Nomor Rekening',
        'Nama Rekening',
        'Nomor Memo',
        'Nilai SPU',
        'Uraian Transaksi',
        'Jenis Jurnal',
        'Status Pembayaran',
        'Tanggal Ekspedisi',
        'Tanggal Pembayaran',
    ];

    private const SPUK_HEADERS = [
        'Nomor Transaksi',
        'Nama Divisi',
        'Softcopy',
        'Hardcopy',
        'Tanggal Submit Transaksi',
        'Nama Bank',
        'Nomor Rekening',
        'Nama Rekening',
        'Nomor SPU',
        'Nilai SPU',
        'Nilai Realisasi',
        'Selisih',
        'Status Jurnal',
        'Tanggal Pengembalian',
        'Nominal Pengembalian',
        'Status SPUK',
        'Notes',
    ];

    private const KK_HEADERS = [
        'Nomor Dokumen',
        'Nama Divisi',
        'Softcopy',
        'Hardcopy',
        'Tanggal Submit Transaksi',
        'Jatuh Tempo',
        'Uraian Transaksi',
        'Nama Bank',
        'Nomor Rekening',
        'Nama Rekening',
        'Plafon Petty Cash',
        'Sisa Petty Cash',
        'Nilai Top Up',
        'Status Dokumen Transaksi',
        'Tanggal Ekspedisi',
        'Status Pembayaran',
        'Tanggal Pembayaran',
    ];

    public function export(Collection $registers, string $path): void
    {
        $sheets = [
            'PPA' => $this->buildPpaRows($registers),
            'SPU' => $this->buildSpuRows($registers),
            'SPUK' => $this->buildSpukRows($registers),
            'KK' => $this->buildKkRows($registers),
        ];

        $this->writeWorkbook($sheets, $path);
    }

    private function buildPpaRows(Collection $registers): array
    {
        $rows = $this->baseRows('PPA', self::PPA_HEADERS);

        foreach ($registers as $register) {
            $type = $this->typeCode($register);

            if (! in_array($type, [TransactionTypeCode::PPA->value, TransactionTypeCode::PPA_NON_CONTRACT->value], true)) {
                continue;
            }

            $transaction = $register->transaction;
            $taxDocument = $this->documentInfo($register, 'PPA_FAKTUR_PAJAK');
            $invoiceValue = (float) ($register->invoice_value ?? 0);
            $ppnValue = (float) ($register->ppn_value ?? 0);
            $pphValue = (float) ($register->pph_value ?? 0);
            $discountValue = (float) ($register->discount_deduction_stamp_value ?? 0);

            $rows[] = [
                $transaction?->registration_number ?: $register->register_number,
                $register->vendor_name,
                $this->checkMark((bool) ($register->upload_date ?? $register->received_date)),
                $this->checkMark((bool) $register->hardcopy_received),
                $this->date($transaction?->submitted_at),
                $this->date($transaction?->submitted_at?->copy()->addDays(30)),
                $register->invoice_number,
                $this->date($register->invoice_date),
                $register->bank_name,
                $register->account_number,
                $register->account_name,
                $register->tax_invoice_number ?? $taxDocument['document_number'] ?? '',
                $this->date($register->tax_invoice_date) ?: ($taxDocument['document_date'] ?? ''),
                $register->memo_number,
                $register->contract_number,
                $this->numberOrBlank($register->contract_value),
                $this->numberOrBlank($invoiceValue),
                $this->numberOrBlank($ppnValue),
                $this->numberOrBlank($pphValue),
                $this->numberOrBlank($discountValue),
                $this->numberOrBlank($invoiceValue + $ppnValue - $pphValue + $discountValue),
                $register->description,
                $register->gr_number,
                $register->journal_type,
                $this->paymentStatus($register),
                $this->date($register->payment_expedition_date ?? $transaction?->scheduled_payment_at),
                $this->date($register->payment_date ?? $transaction?->paid_at),
            ];
        }

        return $rows;
    }

    private function buildSpuRows(Collection $registers): array
    {
        $rows = $this->baseRows('SPU', self::SPU_HEADERS);

        foreach ($registers as $register) {
            if ($this->typeCode($register) !== TransactionTypeCode::SPU->value) {
                continue;
            }

            $transaction = $register->transaction;

            $rows[] = [
                $transaction?->registration_number ?: $register->register_number,
                $register->division_name ?? $transaction?->division?->name,
                $this->checkMark((bool) ($register->upload_date ?? $register->received_date)),
                $this->checkMark((bool) $register->hardcopy_received),
                $this->date($transaction?->submitted_at),
                $register->bank_name,
                $register->account_number,
                $register->account_name,
                $register->memo_number,
                $this->numberOrBlank($transaction?->spu_amount ?? $register->invoice_value),
                $register->description,
                $register->journal_type,
                $this->paymentStatus($register),
                $this->date($register->payment_expedition_date ?? $transaction?->scheduled_payment_at),
                $this->date($register->payment_date ?? $transaction?->paid_at),
            ];
        }

        return $rows;
    }

    private function buildSpukRows(Collection $registers): array
    {
        $rows = $this->baseRows('SPUK', self::SPUK_HEADERS);
        foreach ($registers as $register) {
            if ($this->typeCode($register) !== TransactionTypeCode::SPUK->value) {
                continue;
            }

            $transaction = $register->transaction;

            $rows[] = [
                $transaction?->registration_number ?: $register->register_number,
                $register->division_name ?? $transaction?->division?->name,
                $this->checkMark((bool) ($register->upload_date ?? $register->received_date)),
                $this->checkMark((bool) $register->hardcopy_received),
                $this->date($transaction?->submitted_at),
                $register->bank_name,
                $register->account_number,
                $register->account_name,
                $transaction?->parentSpuTransaction?->registration_number,
                $this->numberOrBlank($transaction?->parentSpuTransaction?->spu_amount ?? $transaction?->spu_amount),
                $this->numberOrBlank($transaction?->accountability_amount),
                $this->numberOrBlank($transaction?->remaining_amount),
                $register->journal_status,
                $this->date($register->return_date),
                $this->numberOrBlank($register->return_amount),
                $register->spuk_status ?: $transaction?->status?->label(),
                $register->spuk_notes,
            ];
        }

        return $rows;
    }

    private function buildKkRows(Collection $registers): array
    {
        $rows = $this->baseRows('PETTY CASH', self::KK_HEADERS);
        foreach ($registers as $register) {
            if ($this->typeCode($register) !== TransactionTypeCode::KAS_KECIL->value) {
                continue;
            }

            $transaction = $register->transaction;
            $topUp = (float) ($transaction?->petty_cash_top_up_amount ?? $register->invoice_value ?? 0);

            $rows[] = [
                $transaction?->registration_number ?: $register->register_number,
                $register->division_name ?? $transaction?->division?->name,
                $this->checkMark((bool) ($register->upload_date ?? $register->received_date)),
                $this->checkMark((bool) $register->hardcopy_received),
                $this->date($transaction?->submitted_at),
                $this->date($transaction?->submitted_at?->copy()->addDays(14)),
                $register->description,
                $register->bank_name,
                $register->account_number,
                $register->account_name,
                $this->numberOrBlank($transaction?->petty_cash_ceiling_snapshot),
                $this->numberOrBlank($transaction?->petty_cash_remaining_amount),
                $this->numberOrBlank($topUp),
                $this->documentStatus($register),
                $this->date($register->payment_expedition_date ?? $transaction?->scheduled_payment_at),
                $this->paymentStatus($register),
                $this->date($register->payment_date ?? $transaction?->paid_at),
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

    private function checkMark(bool $checked): string
    {
        return $checked ? '✓' : '';
    }

    private function date(mixed $date): string
    {
        if (! $date) {
            return '';
        }

        if (is_string($date)) {
            return $date;
        }

        return $date->format('Y-m-d');
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
