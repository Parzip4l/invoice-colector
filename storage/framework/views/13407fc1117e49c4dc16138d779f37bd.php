<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Invoice Verification', 'subtitle' => 'Dashboard'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="row">
    <?php $__currentLoopData = [
        ['label' => 'Total Transaksi', 'value' => $summary['transactions_total'], 'icon' => 'solar:bill-list-outline'],
        ['label' => 'Menunggu Approval', 'value' => $summary['transactions_waiting_approval'], 'icon' => 'solar:checklist-minimalistic-outline'],
        ['label' => 'Dokumen Review Vendor', 'value' => $summary['documents_pending_review'], 'icon' => 'solar:file-warning-outline'],
        ['label' => 'Antrean Finance', 'value' => $summary['transactions_finance_queue'], 'icon' => 'solar:wallet-money-outline'],
        ['label' => 'Arsip Final', 'value' => $summary['compiled_documents'], 'icon' => 'solar:archive-outline'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1"><?php echo e($card['label']); ?></p>
                            <h3 class="mb-0"><?php echo e($card['value']); ?></h3>
                        </div>
                        <div class="avatar-md bg-primary bg-opacity-10 rounded-circle">
                            <iconify-icon icon="<?php echo e($card['icon']); ?>" class="fs-32 text-primary avatar-title"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">Transaksi Terbaru</h5>
                    <p class="text-muted mb-0">Pantau transaksi lintas PPA, SPU, SPUK, dan Kas Kecil.</p>
                </div>
                <a href="<?php echo e(route('invoice-verification.transactions.index')); ?>" class="btn btn-sm btn-primary">Lihat Semua</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Registrasi</th>
                                <th>Jenis</th>
                                <th>Vendor</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $recentTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($transaction->registration_number); ?></div>
                                        <div class="text-muted small"><?php echo e($transaction->title); ?></div>
                                    </td>
                                    <td><?php echo e($transaction->transactionType?->name); ?></td>
                                    <td><?php echo e($transaction->vendor?->name ?? '-'); ?></td>
                                    <td><?php echo $__env->make('invoice-verification.components.status-badge', ['value' => $transaction->status], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></td>
                                    <td class="text-end">
                                        <a href="<?php echo e(route('invoice-verification.transactions.show', $transaction)); ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada transaksi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-1">Approval Queue Terkini</h5>
                <p class="text-muted mb-0">Dokumen awal dan lembar verifikasi yang sedang menunggu approver sebelum masuk scan, upload, dan finance.</p>
            </div>
            <div class="card-body">
                <?php $__empty_1 = true; $__currentLoopData = $approvalQueue; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $approval): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="border rounded-3 p-3 <?php echo e(!$loop->last ? 'mb-3' : ''); ?>">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <div class="fw-semibold"><?php echo e($approval->transaction?->registration_number); ?></div>
                                <p class="text-muted mb-1"><?php echo e($approval->approvalFlow?->step_name); ?></p>
                            </div>
                            <?php echo $__env->make('invoice-verification.components.status-badge', ['value' => $approval->status], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                        </div>
                        <small class="text-muted"><?php echo e($approval->transaction?->title); ?></small>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-muted mb-0">Tidak ada antrean approval saat ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Dashboard'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/dashboard/index.blade.php ENDPATH**/ ?>