<div class="card workflow-card section-card">
    <div class="card-header">
        <div>
            <div class="section-kicker mb-1">Activity</div>
            <h5 class="section-title">Timeline</h5>
        </div>
    </div>
    <div class="card-body">
        <?php if($histories->isNotEmpty()): ?>
            <div class="activity-list">
                <?php $__currentLoopData = $histories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $history): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="activity-item">
                        <span class="activity-node">
                            <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                        </span>
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
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <span class="empty-icon mb-3"><iconify-icon icon="solar:clock-circle-outline"></iconify-icon></span>
                <div class="fw-semibold">Belum ada histori status.</div>
                <div class="text-muted small mt-1">Aktivitas workflow akan muncul setelah transaksi bergerak ke tahap berikutnya.</div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/components/timeline.blade.php ENDPATH**/ ?>