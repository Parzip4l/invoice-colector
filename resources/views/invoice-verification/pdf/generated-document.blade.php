<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        .page { border: 1px solid #111827; padding: 14px; }
        .brand { font-size: 14px; font-weight: bold; color: #4b5563; }
        .title { text-align: center; font-size: 14px; font-weight: bold; margin-top: 4px; }
        .subtitle { text-align: center; font-size: 10px; font-weight: bold; }
        .line { border-top: 1px solid #111827; margin: 10px -14px; }
        .meta, .budget { width: 100%; border-collapse: collapse; }
        .meta td { padding: 4px 6px; vertical-align: top; }
        .meta .label { width: 120px; }
        .budget th, .budget td { border: 1px solid #111827; padding: 7px; }
        .budget th { background: #f3f4f6; text-align: center; font-weight: bold; }
        .right { text-align: right; }
        .center { text-align: center; }
        .signatures { width: 100%; margin-top: 42px; }
        .signatures td { width: 50%; text-align: center; vertical-align: bottom; }
        .muted { color: #4b5563; }
    </style>
</head>
<body>
@php
    $invoiceValue = (float) ($transaction->invoiceMetadata?->invoice_value ?? 0);
    $ppnValue = (float) ($transaction->invoiceMetadata?->ppn_value ?? 0);
    $totalValue = $invoiceValue + $ppnValue;
@endphp
<div class="page">
    <div class="brand">LRT JAKARTA</div>
    <div class="title">PENGAJUAN PENGGUNAAN ANGGARAN</div>
    <div class="subtitle">Nomor: {{ $transaction->registration_number }}</div>

    <div class="line"></div>

    <table class="meta">
        <tr>
            <td class="label">No Dokumen</td>
            <td>: {{ $transaction->registration_number }}</td>
            <td class="right">Halaman: 1 dari 1</td>
        </tr>
        <tr><td class="label">Nama Divisi</td><td colspan="2">: {{ $transaction->division?->name ?? '-' }}</td></tr>
        <tr><td class="label">Nama Departemen</td><td colspan="2">: {{ $transaction->department?->name ?? '-' }}</td></tr>
        <tr><td class="label">Uraian Transaksi</td><td colspan="2">: {{ $transaction->invoiceMetadata?->description ?? $transaction->description ?? '-' }}</td></tr>
        <tr><td class="label">Biaya</td><td colspan="2">: Rp {{ number_format($totalValue, 0, ',', '.') }}</td></tr>
    </table>

    <div class="line"></div>

    <p class="center"><strong>Tahun Anggaran {{ now()->year }}</strong></p>
    <table class="budget">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 130px;">Kode RAB</th>
                <th>Nama Kegiatan</th>
                <th style="width: 140px;">Nilai Realisasi<br>(Setelah PPN)<br>(Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="center">1.</td>
                <td class="center">{{ $transaction->memoRequest?->memo_number ?? '-' }}</td>
                <td>{{ $transaction->invoiceMetadata?->description ?? $transaction->description ?? '-' }}</td>
                <td class="right">{{ number_format($totalValue, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="right"><strong>TOTAL BIAYA</strong></td>
                <td class="right"><strong>{{ number_format($totalValue, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <table class="meta" style="margin-top: 18px;">
        <tr><td class="label">Nilai pekerjaan/Kontrak</td><td>: Rp {{ number_format((float) ($transaction->contract_value ?? $transaction->invoiceMetadata?->contract_value ?? 0), 0, ',', '.') }}</td></tr>
        <tr><td class="label">Nilai Invoice</td><td>: Rp {{ number_format($invoiceValue, 0, ',', '.') }}</td></tr>
        <tr><td class="label">PPN</td><td>: Rp {{ number_format($ppnValue, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Vendor</td><td>: {{ $transaction->vendor?->name ?? '-' }}</td></tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                Kepala Departemen<br>{{ $transaction->department?->name ?? '-' }}
                <br><br><br><br>
                <strong class="muted">________________________</strong>
            </td>
            <td>
                Jakarta, {{ $generatedAt->format('d M Y') }}<br>
                Kepala Divisi<br>{{ $transaction->division?->name ?? '-' }}
                <br><br><br><br>
                <strong class="muted">________________________</strong>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
