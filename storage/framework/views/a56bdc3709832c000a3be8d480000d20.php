<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Finalization', 'subtitle' => 'Numbering Register'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Register Number</th>
                        <th>Vendor</th>
                        <th>Invoice</th>
                        <th>Memo</th>
                        <th>Nilai Invoice</th>
                        <th>Generated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $registers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $register): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($register->register_number); ?></td>
                            <td><?php echo e($register->vendor_name); ?></td>
                            <td><?php echo e($register->invoice_number); ?></td>
                            <td><?php echo e($register->memo_number ?? '-'); ?></td>
                            <td><?php echo e(number_format((float) $register->invoice_value, 2, ',', '.')); ?></td>
                            <td><?php echo e($register->generated_at?->format('d M Y H:i')); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada numbering register.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <?php echo e($registers->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Numbering Register'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/numbering-registers/index.blade.php ENDPATH**/ ?>