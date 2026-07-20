<?php $__env->startSection('css'); ?>
<?php echo $__env->make('invoice-verification.partials.table-ui', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Invoice Verification', 'subtitle' => 'Transactions'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php
    $isRevisionList = request('status') === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value;
?>

<div class="card iv-table-card">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Daftar Transaksi</h5>
                <p class="text-muted mb-0">Cari, filter, dan urutkan transaksi invoice collector.</p>
            </div>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class)): ?>
                <a href="<?php echo e(route('invoice-verification.transactions.create')); ?>" class="btn btn-success d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="solar:add-circle-outline" class="fs-18"></iconify-icon>
                    <span>Buat</span>
                </a>
            <?php endif; ?>
        </div>
        <?php if($isRevisionList): ?>
            <div class="alert alert-warning border-0 mb-3">
                <div class="fw-semibold">Daftar Revisi</div>
                <div>Transaksi di bawah ini memiliki dokumen yang perlu diperbaiki atau diupload ulang oleh Vendor.</div>
            </div>
        <?php endif; ?>
        <form method="GET" class="iv-table-toolbar" style="--iv-filter-count: 2;">
            <div>
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><iconify-icon icon="solar:magnifer-outline" class="fs-18"></iconify-icon></span>
                    <input type="search" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Registrasi, vendor, judul">
                </div>
            </div>
            <div>
                <label class="form-label">Jenis Transaksi</label>
                <select name="transaction_type_id" class="form-select">
                    <option value="">Semua</option>
                    <?php $__currentLoopData = $transactionTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($type->id); ?>" <?php if($transactionTypeId === $type->id): echo 'selected'; endif; ?>><?php echo e($type->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua status</option>
                    <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $statusOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($statusOption->value); ?>" <?php if($status === $statusOption->value): echo 'selected'; endif; ?>><?php echo e($statusOption->label()); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <button class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:filter-outline" class="fs-18"></iconify-icon>
                <span>Filter</span>
            </button>
            <a href="<?php echo e(route('invoice-verification.transactions.index')); ?>" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:restart-outline" class="fs-18"></iconify-icon>
                <span>Reset</span>
            </a>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 iv-table" style="--iv-table-min-width: 1180px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4"><?php echo $__env->make('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.transactions.index', 'column' => 'registration_number', 'label' => 'Registrasi'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></th>
                        <th><?php echo $__env->make('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.transactions.index', 'column' => 'transaction_type', 'label' => 'Jenis'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></th>
                        <th><?php echo $__env->make('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.transactions.index', 'column' => 'vendor', 'label' => 'Vendor'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></th>
                        <th>Invoice</th>
                        <th><?php echo $__env->make('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.transactions.index', 'column' => 'status', 'label' => 'Status'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></th>
                        <th><?php echo $__env->make('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.transactions.index', 'column' => 'current_step', 'label' => 'Step'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></th>
                        <th><?php echo $__env->make('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.transactions.index', 'column' => 'created_at', 'label' => 'Dibuat'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold"><?php echo e($transaction->registration_number); ?></div>
                                <div class="text-muted small text-truncate iv-cell-truncate" title="<?php echo e($transaction->title); ?>"><?php echo e($transaction->title); ?></div>
                            </td>
                            <td><?php echo e($transaction->transactionType?->code?->value ?? '-'); ?></td>
                            <td><div class="text-truncate iv-cell-truncate" style="--iv-cell-width: 190px;" title="<?php echo e($transaction->vendor?->name ?? $transaction->owner?->name ?? '-'); ?>"><?php echo e($transaction->vendor?->name ?? $transaction->owner?->name ?? '-'); ?></div></td>
                            <td><?php echo e($transaction->invoiceMetadata?->invoice_number ?? '-'); ?></td>
                            <td><?php echo $__env->make('invoice-verification.components.status-badge', ['value' => $transaction->status], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></td>
                            <td><div class="text-truncate iv-cell-truncate" style="--iv-cell-width: 180px;" title="<?php echo e($transaction->current_step?->label()); ?>"><?php echo e($transaction->current_step?->label()); ?></div></td>
                            <td><?php echo e($transaction->created_at?->format('d M Y H:i')); ?></td>
                            <td class="text-end">
                                <div class="iv-actions">
                                    <?php if(in_array($transaction->status?->value, ['DRAFT', 'VENDOR_INPUT'], true)): ?>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('uploadDocuments', $transaction)): ?>
                                            <a href="<?php echo e(route('invoice-verification.transactions.documents.show', $transaction)); ?>" class="btn btn-sm btn-primary">Upload</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if($transaction->status?->value === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value): ?>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('uploadDocuments', $transaction)): ?>
                                            <a href="<?php echo e(route('invoice-verification.transactions.documents.show', $transaction)); ?>" class="btn btn-sm btn-warning">Revisi</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <a href="<?php echo e(route('invoice-verification.transactions.show', $transaction)); ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Belum ada transaksi.</td>
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

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Transactions'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/transactions/index.blade.php ENDPATH**/ ?>