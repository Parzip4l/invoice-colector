<?php $__env->startSection('css'); ?>
<style>
    .finance-queue-table {
        min-width: 1260px;
    }

    .finance-queue-table th {
        font-size: .78rem;
        letter-spacing: .02em;
        text-transform: uppercase;
        color: #64748b;
        white-space: nowrap;
    }

    .finance-queue-table td {
        vertical-align: middle;
        padding-top: .65rem;
        padding-bottom: .65rem;
        line-height: 1.25;
    }

    .finance-sort-link {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        color: inherit;
        text-decoration: none;
    }

    .finance-sort-link:hover {
        color: var(--bs-primary);
    }

    .finance-toolbar {
        display: grid;
        grid-template-columns: minmax(280px, 1fr) 190px 170px auto auto;
        gap: .75rem;
        align-items: end;
    }

    .finance-toolbar .form-label {
        font-size: .75rem;
        margin-bottom: .25rem;
    }

    .finance-transaction-title {
        max-width: 420px;
    }

    .finance-vendor-cell {
        max-width: 210px;
    }

    .finance-proof-link {
        max-width: 230px;
    }

    .finance-action-panel {
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
        min-width: 410px;
    }

    .finance-action-panel .form-control {
        min-height: 34px;
    }

    .finance-schedule-form,
    .finance-proof-form {
        display: grid;
        gap: .5rem;
    }

    .finance-schedule-form {
        grid-template-columns: minmax(205px, 1fr) auto;
    }

    .finance-proof-form {
        grid-template-columns: minmax(230px, 1fr) auto;
    }

    .finance-paid-form {
        flex: 0 0 auto;
    }

    @media (max-width: 1199.98px) {
        .finance-toolbar {
            grid-template-columns: 1fr 1fr;
        }

        .finance-action-panel {
            justify-content: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .finance-toolbar {
            grid-template-columns: 1fr;
        }
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $sortIcon = function (string $column) use ($sort, $direction) {
        if ($sort !== $column) {
            return 'solar:alt-arrow-down-outline';
        }

        return $direction === 'asc' ? 'solar:arrow-up-outline' : 'solar:arrow-down-outline';
    };

    $sortUrl = function (string $column) use ($sort, $direction) {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('invoice-verification.finance.index', array_merge(request()->except(['sort', 'direction', 'page']), [
            'sort' => $column,
            'direction' => $nextDirection,
        ]));
    };
?>

<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Finance Queue', 'subtitle' => 'Scheduling Payment dan Paid'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h5 class="card-title mb-1">Transaksi Pembayaran</h5>
                <p class="text-muted mb-0">Transaksi Received dapat dijadwalkan, lalu bukti transfer diunggah sebelum status Paid.</p>
            </div>
            <span class="badge bg-light text-dark border"><?php echo e($transactions->total()); ?> transaksi</span>
        </div>
    </div>
    <div class="card-body border-bottom">
        <form method="GET" action="<?php echo e(route('invoice-verification.finance.index')); ?>" class="finance-toolbar">
            <div>
                <label for="finance-search" class="form-label text-muted">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><iconify-icon icon="solar:magnifer-outline" class="fs-18"></iconify-icon></span>
                    <input id="finance-search" type="search" name="search" value="<?php echo e($search); ?>" class="form-control" placeholder="Nomor, vendor, atau judul transaksi">
                </div>
            </div>
            <div>
                <label for="finance-status" class="form-label text-muted">Status</label>
                <select id="finance-status" name="status" class="form-select">
                    <option value="">Semua status</option>
                    <option value="<?php echo e(\App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::RECEIVED->value); ?>" <?php if($status === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::RECEIVED->value): echo 'selected'; endif; ?>>Received</option>
                    <option value="<?php echo e(\App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::SCHEDULING_PAYMENT->value); ?>" <?php if($status === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::SCHEDULING_PAYMENT->value): echo 'selected'; endif; ?>>Scheduling Payment</option>
                </select>
            </div>
            <div>
                <label for="finance-proof" class="form-label text-muted">Bukti Transfer</label>
                <select id="finance-proof" name="proof" class="form-select">
                    <option value="">Semua bukti</option>
                    <option value="uploaded" <?php if($proof === 'uploaded'): echo 'selected'; endif; ?>>Sudah upload</option>
                    <option value="missing" <?php if($proof === 'missing'): echo 'selected'; endif; ?>>Belum ada</option>
                </select>
            </div>
            <button class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:filter-outline" class="fs-18"></iconify-icon>
                <span>Filter</span>
            </button>
            <a href="<?php echo e(route('invoice-verification.finance.index')); ?>" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:restart-outline" class="fs-18"></iconify-icon>
                <span>Reset</span>
            </a>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 finance-queue-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 28%;">
                            <a href="<?php echo e($sortUrl('registration_number')); ?>" class="finance-sort-link">
                                <span>Transaksi</span>
                                <iconify-icon icon="<?php echo e($sortIcon('registration_number')); ?>" class="fs-14"></iconify-icon>
                            </a>
                        </th>
                        <th style="width: 15%;">
                            <a href="<?php echo e($sortUrl('vendor')); ?>" class="finance-sort-link">
                                <span>Vendor</span>
                                <iconify-icon icon="<?php echo e($sortIcon('vendor')); ?>" class="fs-14"></iconify-icon>
                            </a>
                        </th>
                        <th style="width: 11%;">
                            <a href="<?php echo e($sortUrl('status')); ?>" class="finance-sort-link">
                                <span>Status</span>
                                <iconify-icon icon="<?php echo e($sortIcon('status')); ?>" class="fs-14"></iconify-icon>
                            </a>
                        </th>
                        <th style="width: 10%;">
                            <a href="<?php echo e($sortUrl('scheduled_payment_at')); ?>" class="finance-sort-link">
                                <span>Jadwal</span>
                                <iconify-icon icon="<?php echo e($sortIcon('scheduled_payment_at')); ?>" class="fs-14"></iconify-icon>
                            </a>
                        </th>
                        <th style="width: 14%;">
                            <a href="<?php echo e($sortUrl('payment_proof_file_name')); ?>" class="finance-sort-link">
                                <span>Bukti Transfer</span>
                                <iconify-icon icon="<?php echo e($sortIcon('payment_proof_file_name')); ?>" class="fs-14"></iconify-icon>
                            </a>
                        </th>
                        <th class="text-end pe-4" style="width: 16%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="ps-4">
                                <a href="<?php echo e(route('invoice-verification.transactions.show', $transaction)); ?>" class="fw-semibold text-body">
                                    <?php echo e($transaction->registration_number); ?>

                                </a>
                                <div class="text-muted small text-truncate finance-transaction-title" title="<?php echo e($transaction->title); ?>">
                                    <?php echo e($transaction->title); ?>

                                </div>
                            </td>
                            <td>
                                <div class="fw-medium text-truncate finance-vendor-cell" title="<?php echo e($transaction->vendor?->name ?? $transaction->owner?->name ?? '-'); ?>">
                                    <?php echo e($transaction->vendor?->name ?? $transaction->owner?->name ?? '-'); ?>

                                </div>
                                <?php if($transaction->owner && ! $transaction->vendor): ?>
                                    <div class="text-muted small text-truncate finance-vendor-cell" title="<?php echo e($transaction->owner->department?->name ?? 'Internal'); ?>">
                                        <?php echo e($transaction->owner->department?->name ?? 'Internal'); ?>

                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo e($transaction->status?->badgeClass()); ?> px-2 py-1"><?php echo e($transaction->status?->label()); ?></span>
                            </td>
                            <td>
                                <?php if($transaction->scheduled_payment_at): ?>
                                    <div class="fw-medium"><?php echo e($transaction->scheduled_payment_at->format('d M Y')); ?></div>
                                    <div class="text-muted small"><?php echo e($transaction->scheduled_payment_at->format('H:i')); ?></div>
                                <?php else: ?>
                                    <span class="text-muted">Belum dijadwalkan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($transaction->payment_proof_file_path): ?>
                                    <a href="<?php echo e(route('invoice-verification.finance.payment-proof.preview', $transaction)); ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 finance-proof-link text-truncate" target="_blank" title="<?php echo e($transaction->payment_proof_file_name); ?>">
                                        <iconify-icon icon="solar:file-text-outline" class="fs-16 flex-shrink-0"></iconify-icon>
                                        <span class="text-truncate"><?php echo e($transaction->payment_proof_file_name); ?></span>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Belum ada</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4">
                                <?php if($transaction->status === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::RECEIVED): ?>
                                    <form method="POST" action="<?php echo e(route('invoice-verification.finance.schedule', $transaction)); ?>" class="finance-action-panel finance-schedule-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="datetime-local" name="scheduled_payment_at" class="form-control form-control-sm" required>
                                        <button class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1">
                                            <iconify-icon icon="solar:calendar-add-outline" class="fs-16"></iconify-icon>
                                            <span>Jadwalkan</span>
                                        </button>
                                    </form>
                                <?php elseif($transaction->status === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::SCHEDULING_PAYMENT): ?>
                                    <div class="finance-action-panel">
                                        <form method="POST" action="<?php echo e(route('invoice-verification.finance.payment-proof', $transaction)); ?>" enctype="multipart/form-data" class="finance-proof-form flex-grow-1">
                                            <?php echo csrf_field(); ?>
                                            <input type="file" name="payment_proof" class="form-control form-control-sm" required>
                                            <button class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center gap-1">
                                                <iconify-icon icon="solar:upload-outline" class="fs-16"></iconify-icon>
                                                <span>Upload</span>
                                            </button>
                                        </form>
                                        <form method="POST" action="<?php echo e(route('invoice-verification.finance.paid', $transaction)); ?>" class="finance-paid-form">
                                            <?php echo csrf_field(); ?>
                                            <button class="btn btn-sm btn-success d-inline-flex align-items-center justify-content-center gap-1" <?php if(! $transaction->payment_proof_file_path): echo 'disabled'; endif; ?>>
                                                <iconify-icon icon="solar:check-circle-outline" class="fs-16"></iconify-icon>
                                                <span>Paid</span>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <iconify-icon icon="solar:wallet-money-outline" class="fs-32 d-block mb-2"></iconify-icon>
                                Tidak ada transaksi yang sedang menunggu proses finance.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-top">
            <?php echo e($transactions->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Finance Queue'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/finance/index.blade.php ENDPATH**/ ?>