<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        .box { border: 1px solid #cbd5e1; padding: 24px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 8px; }
        .note { color: #475569; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; }
        td { border: 1px solid #cbd5e1; padding: 8px; vertical-align: top; }
        .label { width: 150px; background: #f8fafc; font-weight: bold; }
    </style>
</head>
<body>
    <div class="box">
        <div class="title">{{ $document['label'] ?? 'Attachment' }}</div>
        <div class="note">
            Lampiran ini tidak berupa PDF merge-ready. Sistem menyisipkan halaman referensi agar bundel final tetap terbentuk dan jejak dokumen tetap terlihat.
        </div>

        <table>
            <tr><td class="label">Transaksi</td><td>{{ $transaction->registration_number }}</td></tr>
            <tr><td class="label">Jenis Sumber</td><td>{{ str($document['source_type'] ?? 'document')->replace('_', ' ')->title() }}</td></tr>
            <tr><td class="label">Nama File</td><td>{{ $document['file_name'] ?? '-' }}</td></tr>
            <tr><td class="label">Ekstensi</td><td>{{ $document['extension'] ?? '-' }}</td></tr>
            <tr><td class="label">Storage Path</td><td>{{ $document['path'] ?? '-' }}</td></tr>
            <tr><td class="label">Resolved Local Path</td><td>{{ $localPath ?? '-' }}</td></tr>
            <tr><td class="label">Keterangan</td><td>Untuk lampiran non-PDF seperti DOCX/XLSX, file asli tetap tersimpan di storage dan harus dibuka dari record sumber bila dibutuhkan.</td></tr>
        </table>
    </div>
</body>
</html>
