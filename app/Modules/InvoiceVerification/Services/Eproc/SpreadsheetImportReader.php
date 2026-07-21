<?php

namespace App\Modules\InvoiceVerification\Services\Eproc;

use Generator;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class SpreadsheetImportReader
{
    public function read(string $path, string $extension): Generator
    {
        $extension = strtolower($extension);

        if (in_array($extension, ['csv', 'txt'], true)) {
            yield from $this->readCsv($path);

            return;
        }

        if ($extension === 'xlsx') {
            yield from $this->readXlsx($path);

            return;
        }

        throw new RuntimeException('Format file import tidak didukung.');
    }

    private function readCsv(string $path): Generator
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('File CSV tidak bisa dibaca.');
        }

        $headers = null;

        while (($row = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = $this->normalizeHeaders($row);

                continue;
            }

            if ($headers === [] || $row === [null]) {
                continue;
            }

            yield $this->combineRow($headers, $row);
        }

        fclose($handle);
    }

    private function readXlsx(string $path): Generator
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP zip belum tersedia di server.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('File XLSX tidak bisa dibuka.');
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetPath = $this->resolveFirstWorksheetPath($zip);
        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('Worksheet pertama tidak ditemukan.');
        }

        $sheet = simplexml_load_string($sheetXml);

        if (! $sheet instanceof SimpleXMLElement) {
            throw new RuntimeException('Worksheet XLSX tidak valid.');
        }

        $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $headers = null;

        $sheet->registerXPathNamespace('x', $namespace);

        foreach ($sheet->xpath('//x:sheetData/x:row') ?: [] as $rowNode) {
            $row = [];
            $rowNode->registerXPathNamespace('x', $namespace);

            foreach ($rowNode->xpath('x:c') ?: [] as $cell) {
                $index = $this->columnIndex((string) $cell['r']);
                $row[$index] = $this->cellValue($cell, $sharedStrings, $namespace);
            }

            if ($row === []) {
                continue;
            }

            ksort($row);
            $denseRow = [];
            $maxIndex = max(array_keys($row));

            for ($index = 0; $index <= $maxIndex; $index++) {
                $denseRow[] = $row[$index] ?? null;
            }

            if ($headers === null) {
                $headers = $this->normalizeHeaders($denseRow);

                continue;
            }

            if ($headers !== []) {
                yield $this->combineRow($headers, $denseRow);
            }
        }
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $shared = simplexml_load_string($xml);

        if (! $shared instanceof SimpleXMLElement) {
            return [];
        }

        $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $strings = [];

        foreach ($shared->children($namespace)->si as $item) {
            $text = '';

            foreach ($item->children($namespace)->t as $node) {
                $text .= (string) $node;
            }

            foreach ($item->children($namespace)->r as $run) {
                $text .= (string) $run->children($namespace)->t;
            }

            $strings[] = $text;
        }

        return $strings;
    }

    private function resolveFirstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relationshipsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relationshipsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        $relationships = simplexml_load_string($relationshipsXml);

        if (! $workbook instanceof SimpleXMLElement || ! $relationships instanceof SimpleXMLElement) {
            return 'xl/worksheets/sheet1.xml';
        }

        $mainNamespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $relationshipNamespace = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $firstSheet = $workbook->children($mainNamespace)->sheets->children($mainNamespace)->sheet[0] ?? null;

        if (! $firstSheet) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationshipId = (string) $firstSheet->attributes($relationshipNamespace)->id;

        foreach ($relationships->Relationship as $relationship) {
            if ((string) $relationship['Id'] !== $relationshipId) {
                continue;
            }

            $target = (string) $relationship['Target'];

            if (str_starts_with($target, '/')) {
                return ltrim($target, '/');
            }

            return 'xl/'.ltrim($target, '/');
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function cellValue(SimpleXMLElement $cell, array $sharedStrings, string $namespace): ?string
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            $index = (int) $cell->children($namespace)->v;

            return $sharedStrings[$index] ?? null;
        }

        if ($type === 'inlineStr') {
            return (string) $cell->children($namespace)->is->children($namespace)->t;
        }

        $value = (string) $cell->children($namespace)->v;

        return $value === '' ? null : $value;
    }

    private function normalizeHeaders(array $row): array
    {
        $headers = [];

        foreach ($row as $index => $header) {
            $header = trim((string) $header);

            if ($header !== '') {
                $headers[$index] = $header;
            }
        }

        return $headers;
    }

    private function combineRow(array $headers, array $row): array
    {
        $combined = [];

        foreach ($headers as $index => $header) {
            $combined[$header] = $row[$index] ?? null;
        }

        return $combined;
    }

    private function columnIndex(string $reference): int
    {
        if (! preg_match('/^([A-Z]+)/i', $reference, $matches)) {
            return 0;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }
}
