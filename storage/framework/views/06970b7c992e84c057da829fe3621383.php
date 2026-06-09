<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Invoice Verification', 'subtitle' => 'Transactions'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php
    $isRevisionList = request('status') === 'REVISION_IN_PROGRESS';
?>

<div class="card">
    <div class="card-header">
        <?php if($isRevisionList): ?>
            <div class="alert alert-warning border-0 mb-3">
                <div class="fw-semibold">Daftar Revisi</div>
                <div>Transaksi di bawah ini memiliki dokumen yang perlu diperbaiki atau diupload ulang oleh Vendor.</div>
            </div>
        <?php endif; ?>
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Cari</label>
                <input type="text" class="form-control" name="search" value="<?php echo e(request('search')); ?>" placeholder="registrasi, vendor, judul">
            </div>
            <div class="col-md-3">
                <label class="form-label">Jenis Transaksi</label>
                <select name="transaction_type_id" class="form-select">
                    <option value="">Semua</option>
                    <?php $__currentLoopData = $transactionTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($type->id); ?>" <?php if(request('transaction_type_id') === $type->id): echo 'selected'; endif; ?>><?php echo e($type->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <input type="text" class="form-control" name="status" value="<?php echo e(request('status')); ?>" placeholder="WAITING_APPROVAL">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100">Filter</button>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class)): ?>
                    <a href="<?php echo e(route('invoice-verification.transactions.create')); ?>" class="btn btn-success w-100">Buat</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Registrasi</th>
                        <th>Jenis</th>
                        <th>Vendor</th>
                        <th>Invoice</th>
                        <th>Status</th>
                        <th>Step</th>
                        <th>Dibuat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo e($transaction->registration_number); ?></div>
                                <div class="text-muted small"><?php echo e($transaction->title); ?></div>
                            </td>
                            <td><?php echo e($transaction->transactionType?->code?->value ?? '-'); ?></td>
                            <td><?php echo e($transaction->vendor?->name ?? '-'); ?></td>
                            <td><?php echo e($transaction->invoiceMetadata?->invoice_number ?? '-'); ?></td>
                            <td><?php echo $__env->make('invoice-verification.components.status-badge', ['value' => $transaction->status], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></td>
                            <td><?php echo e($transaction->current_step?->label()); ?></td>
                            <td><?php echo e($transaction->created_at?->format('d M Y H:i')); ?></td>
                            <td class="text-end">
                                <?php if(in_array($transaction->status?->value, ['DRAFT', 'VENDOR_INPUT'], true)): ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('uploadDocuments', $transaction)): ?>
                                        <a href="<?php echo e(route('invoice-verification.transactions.documents.show', $transaction)); ?>" class="btn btn-sm btn-primary">Upload Dokumen</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if($transaction->status?->value === 'REVISION_IN_PROGRESS'): ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('uploadDocuments', $transaction)): ?>
                                        <a href="<?php echo e(route('invoice-verification.transactions.documents.show', $transaction)); ?>" class="btn btn-sm btn-warning">Upload Ulang</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <a href="<?php echo e(route('invoice-verification.transactions.show', $transaction)); ?>" class="btn btn-sm btn-outline-primary">Detail</a>
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

        <div class="mt-3">
            <?php echo e($transactions->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Transactions'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/transactions/index.blade.php ENDPATH**/ ?>