@php
    use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;

    $status = $transaction->status;
    $statusValue = $status?->value;
    $isComplete = in_array($statusValue, [
        TransactionStatus::PAID->value,
        TransactionStatus::COMPLETED->value,
        TransactionStatus::ARCHIVED->value,
    ], true);
    $isRevision = in_array($statusValue, [
        TransactionStatus::NOT_APPROVED->value,
        TransactionStatus::REVISION_IN_PROGRESS->value,
    ], true);

    $stage = match ($status) {
        TransactionStatus::RECEIVED,
        TransactionStatus::SCHEDULING_PAYMENT,
        TransactionStatus::FINANCE_PROCESS => 2,
        TransactionStatus::PAID,
        TransactionStatus::COMPLETED,
        TransactionStatus::ARCHIVED => 3,
        default => 1,
    };

    [$title, $description, $icon] = match ($status) {
        TransactionStatus::DRAFT => [
            'Draft transaksi',
            'Transaksi masih berupa draft dan belum dikirim.',
            'solar:document-add-outline',
        ],
        TransactionStatus::VENDOR_INPUT => [
            'Menunggu kelengkapan vendor',
            'Data tagihan dan dokumen sedang dilengkapi oleh vendor.',
            'solar:document-text-outline',
        ],
        TransactionStatus::SUBMITTED,
        TransactionStatus::DOCUMENT_COLLECTION => [
            'Menunggu review tim Accounting',
            'Dokumen telah dikirim dan menunggu pemeriksaan.',
            'solar:inbox-line-outline',
        ],
        TransactionStatus::IN_REVIEW,
        TransactionStatus::ADMIN_REVIEW,
        TransactionStatus::WAITING_APPROVAL,
        TransactionStatus::ADMIN_GENERATE_DOCUMENTS,
        TransactionStatus::VENDOR_REVIEW,
        TransactionStatus::ACCOUNTING_VERIFICATION => [
            'Sedang direview tim Accounting',
            'Dokumen transaksi sedang dalam proses pemeriksaan.',
            'solar:checklist-minimalistic-outline',
        ],
        TransactionStatus::NOT_APPROVED,
        TransactionStatus::REVISION_IN_PROGRESS => [
            'Perlu revisi dokumen',
            'Dokumen perlu diperbaiki sebelum proses dilanjutkan.',
            'solar:danger-triangle-outline',
        ],
        TransactionStatus::RECEIVED => [
            'Dokumen telah diterima',
            'Dokumen lengkap dan siap diproses oleh tim Finance.',
            'solar:inbox-in-outline',
        ],
        TransactionStatus::SCHEDULING_PAYMENT => [
            'Menunggu jadwal pembayaran',
            'Pembayaran sedang dijadwalkan oleh tim Finance.',
            'solar:calendar-mark-outline',
        ],
        TransactionStatus::FINANCE_PROCESS => [
            'Proses pembayaran',
            'Transaksi sedang diproses oleh tim Finance.',
            'solar:wallet-money-outline',
        ],
        TransactionStatus::PAID => [
            'Pembayaran selesai',
            'Pembayaran transaksi telah berhasil diselesaikan.',
            'solar:check-circle-bold',
        ],
        TransactionStatus::COMPLETED => [
            'Transaksi selesai',
            'Seluruh proses transaksi telah selesai.',
            'solar:check-circle-bold',
        ],
        TransactionStatus::ARCHIVED => [
            'Transaksi selesai',
            'Transaksi selesai dan telah diarsipkan.',
            'solar:archive-check-outline',
        ],
        default => [
            $status?->label() ?? 'Belum diproses',
            'Status transaksi belum tersedia.',
            'solar:inbox-line-outline',
        ],
    };

    $tone = $isComplete ? 'success' : ($isRevision ? 'danger' : 'warning');
    $progress = (int) round(($stage / 3) * 100);
@endphp

<div class="iv-transaction-progress is-{{ $tone }}" aria-label="{{ $title }}, tahap {{ $stage }} dari 3">
    <span class="iv-transaction-progress__icon" aria-hidden="true">
        <iconify-icon icon="{{ $icon }}"></iconify-icon>
    </span>
    <div class="iv-transaction-progress__content">
        <div class="iv-transaction-progress__heading">
            <span class="iv-transaction-progress__title">{{ $title }}</span>
            <span class="iv-transaction-progress__stage">{{ $isComplete ? 'Selesai' : 'Tahap '.$stage.'/3' }}</span>
        </div>
        <p class="iv-transaction-progress__description">{{ $description }}</p>
        <div
            class="iv-transaction-progress__track"
            role="progressbar"
            aria-valuenow="{{ $progress }}"
            aria-valuemin="0"
            aria-valuemax="100"
        >
            <span class="iv-transaction-progress__bar" style="width: {{ $progress }}%"></span>
        </div>
    </div>
</div>
