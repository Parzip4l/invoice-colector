<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Audit Logs', 'subtitle' => 'Tracking'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="card">
    <div class="card-header">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="module" value="<?php echo e(request('module')); ?>" placeholder="Filter module">
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" name="action" value="<?php echo e(request('action')); ?>" placeholder="Filter action">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="transaction_id" value="<?php echo e(request('transaction_id')); ?>" placeholder="Transaction ID">
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100">Go</button>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Created At</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Transaction</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $auditLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $auditLog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($auditLog->created_at?->format('d M Y H:i:s')); ?></td>
                            <td><?php echo e($auditLog->module); ?></td>
                            <td><?php echo e($auditLog->action); ?></td>
                            <td><?php echo e($auditLog->transaction_id ?? '-'); ?></td>
                            <td><?php echo e($auditLog->reference_type ? class_basename($auditLog->reference_type) : '-'); ?> <?php echo e($auditLog->reference_id ?? ''); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada audit log.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <?php echo e($auditLogs->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Audit Logs'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/audit-logs/index.blade.php ENDPATH**/ ?>