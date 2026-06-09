<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; }
        .header { text-align: center; margin-bottom: 8px; }
        .brand { float: left; font-size: 13px; font-weight: bold; color: #4b5563; }
        .title { font-size: 12px; font-weight: bold; letter-spacing: 2px; }
        .subtitle { font-size: 10px; font-weight: bold; }
        .meta, .table { width: 100%; border-collapse: collapse; }
        .meta td, .table th, .table td { border: 1px solid #111827; padding: 5px; vertical-align: top; }
        .table th { background: #f3f4f6; text-align: center; }
        .center { text-align: center; }
        .signatures { width: 100%; margin-top: 28px; }
        .signatures td { width: 50%; text-align: center; }
    </style>
</head>
<body>
<?php
    $transaction = $sheet->transaction;
?>
<div class="header">
    <div class="brand">LRT JAKARTA</div>
    <div class="title">LEMBAR VERIFIKASI</div>
    <div class="subtitle">CHECK LIST - DOKUMEN PENDUKUNG PEMBAYARAN</div>
</div>

<table class="meta">
    <tr>
        <td>No Dokumen: <?php echo e($transaction?->registration_number); ?></td>
        <td>Nomor Revisi: 0</td>
        <td>Halaman: 1 dari 1</td>
    </tr>
    <tr>
        <td colspan="3">Divisi/Departemen: <?php echo e($transaction?->division?->name ?? '-'); ?> / <?php echo e($transaction?->department?->name ?? '-'); ?></td>
    </tr>
    <tr>
        <td colspan="3">Pekerjaan/Kegiatan: <?php echo e($transaction?->invoiceMetadata?->description ?? $transaction?->description ?? '-'); ?></td>
    </tr>
</table>

<table class="table" style="margin-top: 8px;">
    <thead>
        <tr>
            <th style="width: 28px;">No</th>
            <th>Nama Dokumen</th>
            <th style="width: 90px;">Dibutuhkan<br>(Ya / Tidak)</th>
            <th style="width: 90px;">Aktual<br>(Ada / Tidak Ada)</th>
            <th>Keterangan</th>
            <th style="width: 100px;">Verifikasi<br>(Tersedia / Tidak)</th>
        </tr>
    </thead>
    <tbody>
        <?php $__currentLoopData = $sheet->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $isAttached = $item->attachment_status?->value === 'ATTACHED';
                $document = $transaction?->latestDocuments?->firstWhere('document_type_id', $item->document_type_id);
                $documentNumber = data_get($document?->document_information_json, 'document_number', $item->notes ?: '-');
            ?>
            <tr>
                <td class="center"><?php echo e($loop->iteration); ?></td>
                <td><?php echo e($item->documentType?->name); ?></td>
                <td class="center"><?php echo e($isAttached ? 'YA' : 'TIDAK'); ?></td>
                <td class="center"><?php echo e($isAttached ? 'ADA' : 'TIDAK ADA'); ?></td>
                <td><?php echo e($documentNumber ?: '-'); ?></td>
                <td class="center"><?php echo e($isAttached ? 'TERSEDIA' : 'TIDAK TERSEDIA'); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>

<table class="signatures">
    <tr>
        <td>
            Kepala Divisi<br><?php echo e($transaction?->division?->name ?? '-'); ?>

            <br><br><br><br>
            ________________________
        </td>
        <td>
            Jakarta, <?php echo e($generatedAt->format('d M Y')); ?><br>
            Akuntansi
            <br><br><br><br>
            ________________________
        </td>
    </tr>
</table>

<p style="margin-top: 24px;">Catatan: Kolom keterangan diisi dengan nomor dokumen.</p>
</body>
</html>
<?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/pdf/ppa-verification-sheet.blade.php ENDPATH**/ ?>