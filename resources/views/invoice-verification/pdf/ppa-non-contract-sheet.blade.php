<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PPA Non Kontrak - {{ $transaction->registration_number }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 7mm 10mm 6mm 10mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7.5pt;
            color: #111827;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }
        .page-container {
            border: 1.2px solid #000;
            padding: 7px 10px;
            box-sizing: border-box;
        }
        .page-break {
            page-break-after: always;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-logo {
            width: 115px;
        }
        .header-logo img {
            max-width: 110px;
            max-height: 36px;
        }
        .header-title-box {
            text-align: center;
        }
        .main-title {
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 0.3px;
            margin: 0;
        }
        .sub-title {
            font-size: 7.5pt;
            font-weight: bold;
            margin-top: 1px;
        }
        .meta-box-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            margin-bottom: 4px;
        }
        .meta-box-table td {
            padding: 1.5px 4px;
            font-size: 7pt;
            vertical-align: top;
        }
        .meta-header-row td {
            border-bottom: 1px solid #000;
            font-size: 7pt;
            padding: 2px 4px;
        }
        .border-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            margin-bottom: 4px;
        }
        .border-table th, .border-table td {
            border: 1px solid #000;
            padding: 2.5px 4px;
            font-size: 7pt;
        }
        .border-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .checklist-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            margin-bottom: 2px;
        }
        .checklist-table th, .checklist-table td {
            border: 1px solid #000;
            padding: 1.5px 3.5px;
            font-size: 6.7pt;
            line-height: 1.15;
        }
        .checklist-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
            font-size: 6.7pt;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .summary-table td {
            padding: 1.5px 3px;
            font-size: 7pt;
            vertical-align: top;
        }
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            page-break-inside: avoid;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 1px 4px;
        }
        .qr-box {
            display: inline-block;
            margin: 2px auto;
            text-align: center;
        }
        .qr-img {
            width: 46px;
            height: 46px;
        }
        .qr-label {
            font-size: 5.5pt;
            color: #4b5563;
            margin-top: 1px;
        }
        .qr-placeholder {
            height: 44px;
            line-height: 44px;
            font-size: 6.5pt;
            color: #9ca3af;
            font-style: italic;
        }
        .category-header td {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 6.7pt;
            padding: 1.5px 3.5px;
        }
        .footer-note {
            font-size: 6.2pt;
            font-style: italic;
            margin-top: 2px;
        }
    </style>
</head>
<body>

{{-- ==================== HALAMAN 1: PENGAJUAN PENGGUNAAN ANGGARAN ==================== --}}
<div class="page-container">
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="LRT Jakarta">
                @else
                    <div style="font-weight: bold; font-size: 13pt; color: #e21a1a;">LRT JAKARTA</div>
                @endif
            </td>
            <td class="header-title-box">
                <div class="main-title">PENGAJUAN PENGGUNAAN ANGGARAN</div>
                <div class="sub-title">Nomor : {{ $transaction->registration_number }}</div>
            </td>
            <td style="width: 100px;"></td>
        </tr>
    </table>

    <table class="meta-box-table">
        <tr class="meta-header-row">
            <td style="width: 38%;">No Dokumen &nbsp;: <strong>LRTJ-FR-KDA-001</strong></td>
            <td style="width: 32%;">Nomor Revisi : <strong>02</strong></td>
            <td style="width: 30%; text-align: right;">Halaman : <strong>1 dari 1</strong></td>
        </tr>
        <tr>
            <td style="width: 25%;">Nama Divisi</td>
            <td colspan="2">: {{ $transaction->division?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Nama Departemen</td>
            <td colspan="2">: {{ $transaction->department?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Uraian Transaksi</td>
            <td colspan="2">: {{ $transaction->invoiceMetadata?->description ?? $transaction->description ?? '-' }}</td>
        </tr>
        <tr>
            <td>Biaya</td>
            <td colspan="2">: <strong>Rp {{ number_format($totalRealization, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <div class="text-center fw-bold" style="font-size: 7.8pt; margin-bottom: 2px;">
        Tahun Anggaran {{ $budgetYear }}
    </div>

    <table class="border-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 130px;">Kode RAB</th>
                <th>Nama Kegiatan</th>
                <th style="width: 140px;">Nilai Realisasi<br>(Setelah PPN) (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($budgetItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $item['kode_rab'] ?? '-' }}</td>
                    <td>{{ $item['nama_kegiatan'] ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format((float) ($item['nilai_realisasi'] ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-center">1</td>
                    <td class="text-center">-</td>
                    <td>{{ $transaction->invoiceMetadata?->description ?? $transaction->description ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($totalRealization, 0, ',', '.') }}</td>
                </tr>
            @endforelse
            <tr class="fw-bold">
                <td colspan="3" class="text-center" style="background-color: #f2f2f2;">TOTAL BIAYA</td>
                <td class="text-right" style="background-color: #f2f2f2;">Rp {{ number_format($totalRealization, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td style="width: 32%;">Nilai Pekerjaan/Kontrak</td>
            <td style="width: 3%;">:</td>
            <td style="width: 30%;">Rp {{ number_format($contractValue, 0, ',', '.') }}</td>
            <td style="width: 35%;"></td>
        </tr>
        <tr>
            <td>UM/Termin yang telah dibayar</td>
            <td>:</td>
            <td>Rp {{ number_format($advancePaymentValue, 0, ',', '.') }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Nilai Invoice</td>
            <td>:</td>
            <td>Rp {{ number_format($invoiceValue, 0, ',', '.') }}</td>
            <td>Nilai Kurs &nbsp;&nbsp;&nbsp;: Rp {{ number_format($exchangeRate, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Terbilang</td>
            <td>:</td>
            <td colspan="2" class="fw-bold" style="font-style: italic;">
                {{ $terbilang }}
            </td>
        </tr>
    </table>

    <div style="margin-top: 6px; text-align: right; font-size: 7.2pt; padding-right: 14px;">
        Jakarta, {{ $signatureDate }}<br>
        <strong>PT LRT JAKARTA</strong><br>
        Penggunaan Anggaran Disetujui Oleh :
    </div>

    <table class="signatures-table">
        <tr>
            <td>
                <div class="fw-bold" style="font-size: 7.5pt;">{{ $kadepTitle }}</div>
                <div class="qr-box">
                    @if(!empty($kadepQrBase64))
                        <img src="{{ $kadepQrBase64 }}" class="qr-img" alt="QR Kadep">
                        <div class="qr-label">Tersertifikasi Portal LRTJ</div>
                    @else
                        <div class="qr-placeholder">(Menunggu Approval)</div>
                    @endif
                </div>
                <div class="fw-bold" style="font-size: 7.5pt; text-decoration: underline;">{{ $kadepName }}</div>
                <div style="font-size: 6.8pt;">NIK. {{ $kadepNik }}</div>
            </td>
            <td>
                <div class="fw-bold" style="font-size: 7.5pt;">{{ $kadivTitle }}</div>
                <div class="qr-box">
                    @if(!empty($kadivQrBase64))
                        <img src="{{ $kadivQrBase64 }}" class="qr-img" alt="QR Kadiv">
                        <div class="qr-label">Tersertifikasi Portal LRTJ</div>
                    @else
                        <div class="qr-placeholder">(Menunggu Approval)</div>
                    @endif
                </div>
                <div class="fw-bold" style="font-size: 7.5pt; text-decoration: underline;">{{ $kadivName }}</div>
                <div style="font-size: 6.8pt;">NIK. {{ $kadivNik }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="page-break"></div>

{{-- ==================== HALAMAN 2: LEMBAR VERIFIKASI CHECK LIST ==================== --}}
<div class="page-container">
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="LRT Jakarta">
                @else
                    <div style="font-weight: bold; font-size: 13pt; color: #e21a1a;">LRT JAKARTA</div>
                @endif
            </td>
            <td class="header-title-box">
                <div class="main-title">LEMBAR VERIFIKASI</div>
                <div class="sub-title">CHECK LIST - DOKUMEN PENDUKUNG PEMBAYARAN</div>
            </td>
            <td style="width: 100px;"></td>
        </tr>
    </table>

    <table class="meta-box-table">
        <tr class="meta-header-row">
            <td style="width: 38%;">No Dokumen &nbsp;: <strong>LRTJ-FR-KDA-004</strong></td>
            <td style="width: 32%;">Nomor Revisi : <strong>04</strong></td>
            <td style="width: 30%; text-align: right;">Halaman : <strong>Page 1 of 1</strong></td>
        </tr>
        <tr>
            <td style="width: 25%;">Divisi/Departemen</td>
            <td colspan="2">: {{ $transaction->division?->name ?? '-' }} / {{ $transaction->department?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Pekerjaan/Kegiatan</td>
            <td colspan="2">: {{ $transaction->invoiceMetadata?->description ?? $transaction->description ?? '-' }}</td>
        </tr>
    </table>

    <table class="checklist-table">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th>Dokumen</th>
                <th style="width: 75px;">DIBUTUHKAN<br>(Ya / Tidak)</th>
                <th style="width: 75px;">AKTUAL<br>(Ada / Tidak Ada)</th>
                <th style="width: 140px;">KETERANGAN</th>
                <th style="width: 85px;">Verifikasi<br>(Tersedia / Tidak)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($checklistCategories as $cat)
                <tr class="category-header">
                    <td class="text-center fw-bold">{{ $cat['roman'] }}</td>
                    <td colspan="5" class="fw-bold">{{ $cat['title'] }}</td>
                </tr>
                @foreach($cat['items'] as $item)
                    <tr>
                        <td class="text-center">{{ $item['no'] }}</td>
                        <td style="{{ !empty($item['is_sub']) ? 'padding-left: 14px;' : '' }}">{{ $item['name'] }}</td>
                        <td class="text-center">{{ !empty($item['is_required']) ? 'Ya' : 'Tidak' }}</td>
                        <td class="text-center">{{ !empty($item['is_actual']) ? 'Ada' : 'Tidak Ada' }}</td>
                        <td>{{ $item['notes'] ?? '-' }}</td>
                        <td class="text-center">{{ !empty($item['verification_status']) ? $item['verification_status'] : '-' }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="footer-note">
        Catatan: Kolom "keterangan" diisi dengan Nomor Dokumen
    </div>

    <div style="margin-top: 4px; text-align: right; font-size: 7.2pt; padding-right: 14px;">
        Jakarta, {{ $signatureDate }}
    </div>

    <table class="signatures-table">
        <tr>
            <td>
                <div class="fw-bold" style="font-size: 7.5pt;">{{ $kadivTitle }}</div>
                <div class="qr-box">
                    @if(!empty($kadivQrBase64))
                        <img src="{{ $kadivQrBase64 }}" class="qr-img" alt="QR Kadiv">
                        <div class="qr-label">Tersertifikasi Portal LRTJ</div>
                    @else
                        <div class="qr-placeholder">(Menunggu Approval)</div>
                    @endif
                </div>
                <div class="fw-bold" style="font-size: 7.5pt; text-decoration: underline;">{{ $kadivName }}</div>
                <div style="font-size: 6.8pt;">NIK. {{ $kadivNik }}</div>
            </td>
            <td>
                <div class="fw-bold" style="font-size: 7.5pt;">Kepala Divisi Keuangan dan Akuntansi</div>
                <div class="qr-box">
                    @if(!empty($accountingQrBase64))
                        <img src="{{ $accountingQrBase64 }}" class="qr-img" alt="QR Akuntansi">
                        <div class="qr-label">Tersertifikasi Akuntansi LRTJ</div>
                    @else
                        <div class="qr-placeholder" style="border-bottom: 1px solid #000; height: 38px; line-height: 38px; margin: 4px 30px;">(Tanda Tangan)</div>
                    @endif
                </div>
                <div class="fw-bold" style="font-size: 7.5pt; text-decoration: underline; margin-top: 2px;">{{ $accountingName }}</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
