<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::KEPALA_DEPARTEMEN) ? 'Kadep Review' : 'Kadiv Review', 'subtitle' => 'Approval Transaksi'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transaksi</th>
                        <th>Tahap</th>
                        <th>Status</th>
                        <th>Catatan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $approvalTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $approvalTransaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo e($approvalTransaction->transaction?->registration_number); ?></div>
                                <div class="text-muted small"><?php echo e($approvalTransaction->transaction?->title); ?></div>
                                <a href="<?php echo e(route('invoice-verification.transactions.show', $approvalTransaction->transaction)); ?>" class="btn btn-sm btn-link px-0">Lihat Detail & Dokumen Vendor</a>
                            </td>
                            <td><?php echo e($approvalTransaction->approvalFlow?->step_name); ?></td>
                            <td><?php echo $__env->make('invoice-verification.components.status-badge', ['value' => $approvalTransaction->status], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></td>
                            <td><?php echo e($approvalTransaction->notes ?? '-'); ?></td>
                            <td class="text-end">
                                <?php if($approvalTransaction->status->value === 'PENDING'): ?>
                                    <form method="POST" action="<?php echo e(route('invoice-verification.approvals.update', $approvalTransaction)); ?>" class="d-inline-flex gap-2">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>
                                        <input type="hidden" name="status" value="APPROVED">
                                        <button class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="<?php echo e(route('invoice-verification.approvals.update', $approvalTransaction)); ?>" class="d-inline-flex gap-2 ms-1">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>
                                        <input type="hidden" name="status" value="REJECTED">
                                        <input type="hidden" name="notes" value="Dikembalikan untuk revisi dokumen awal.">
                                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Tidak ada approval yang ditugaskan ke Anda.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <?php echo e($approvalTransactions->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Approval Queue'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/approvals/index.blade.php ENDPATH**/ ?>