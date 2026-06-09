<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Timeline</h5>
    </div>
    <div class="card-body">
        <?php $__empty_1 = true; $__currentLoopData = $histories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $history): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="d-flex gap-3 <?php echo e(!$loop->last ? 'pb-3 mb-3 border-bottom' : ''); ?>">
                <div>
                    <div class="avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                        <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between gap-3 flex-wrap">
                        <div>
                            <h6 class="mb-1"><?php echo e(str($history->to_status)->replace('_', ' ')->title()); ?></h6>
                            <p class="text-muted mb-1">Step: <?php echo e(str($history->to_step)->replace('_', ' ')->title()); ?></p>
                        </div>
                        <small class="text-muted"><?php echo e($history->created_at?->format('d M Y H:i')); ?></small>
                    </div>
                    <?php if($history->notes): ?>
                        <p class="mb-1"><?php echo e($history->notes); ?></p>
                    <?php endif; ?>
                    <small class="text-muted">Oleh: <?php echo e($history->changer?->name ?? 'System'); ?></small>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-muted mb-0">Belum ada histori perubahan status.</p>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/components/timeline.blade.php ENDPATH**/ ?>