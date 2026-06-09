<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Generated Documents', 'subtitle' => 'Detail'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <div>
                        <h4 class="mb-1"><?php echo e(str($generatedDocument->document_code)->replace('_', ' ')->title()); ?></h4>
                        <p class="text-muted mb-0"><?php echo e($generatedDocument->transaction?->registration_number); ?></p>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold"><?php echo e($generatedDocument->document_number ?? '-'); ?></div>
                        <div class="text-muted small">Generated <?php echo e($generatedDocument->generated_at?->format('d M Y H:i')); ?></div>
                    </div>
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Generation Status</small>
                            <div class="fw-semibold"><?php echo e($generatedDocument->generation_status->value); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Approval Mode</small>
                            <div class="fw-semibold"><?php echo e($generatedDocument->approval_mode->value); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Template</small>
                            <div class="fw-semibold"><?php echo e($generatedDocument->templateReference?->name ?? 'Default placeholder'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Approval Steps</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-centered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Step</th>
                                <th>Approver</th>
                                <th>Status</th>
                                <th>Action At</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $generatedDocument->approvals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $approval): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($approval->approvalFlow?->step_name); ?></td>
                                    <td><?php echo e($approval->approver?->name); ?></td>
                                    <td><?php echo $__env->make('invoice-verification.components.status-badge', ['value' => $approval->status], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></td>
                                    <td><?php echo e($approval->action_at?->format('d M Y H:i') ?? '-'); ?></td>
                                    <td><?php echo e($approval->notes ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada alur approval.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Generated Document Detail'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/generated-documents/show.blade.php ENDPATH**/ ?>