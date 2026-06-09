<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'PPA Verification Sheet', 'subtitle' => 'Checklist Form'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><?php echo e($transaction->registration_number); ?></h5>
            <p class="text-muted mb-0">Checklist terstruktur untuk membandingkan kelengkapan dokumen PPA dengan upload aktual.</p>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="<?php echo e(route('invoice-verification.transactions.ppa-verification-sheets.submit', $transaction)); ?>">
                <?php echo csrf_field(); ?>
                <button class="btn btn-primary">Submit for Approval</button>
            </form>
            <?php if(auth()->user()?->role_code?->value === 'KEPALA_DIVISI'): ?>
                <form method="POST" action="<?php echo e(route('invoice-verification.transactions.ppa-verification-sheets.decision', $transaction)); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="decision" value="APPROVED">
                    <button class="btn btn-success">Approve</button>
                </form>
                <form method="POST" action="<?php echo e(route('invoice-verification.transactions.ppa-verification-sheets.decision', $transaction)); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="decision" value="REJECTED">
                    <input type="hidden" name="notes" value="Checklist belum sesuai dengan dokumen pendukung.">
                    <button class="btn btn-outline-danger">Reject</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="<?php echo e(route('invoice-verification.transactions.ppa-verification-sheets.update', $transaction)); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <div class="table-responsive">
                <table class="table table-centered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Dokumen</th>
                            <th>Attachment Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $sheet->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo e($item->documentType?->name); ?></div>
                                    <input type="hidden" name="items[<?php echo e($index); ?>][document_type_id]" value="<?php echo e($item->document_type_id); ?>">
                                </td>
                                <td>
                                    <select class="form-select" name="items[<?php echo e($index); ?>][attachment_status]">
                                        <option value="ATTACHED" <?php if(($item->attachment_status?->value ?? null) === 'ATTACHED'): echo 'selected'; endif; ?>>ATTACHED</option>
                                        <option value="NOT_ATTACHED" <?php if(($item->attachment_status?->value ?? null) === 'NOT_ATTACHED'): echo 'selected'; endif; ?>>NOT_ATTACHED</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="items[<?php echo e($index); ?>][notes]" value="<?php echo e($item->notes); ?>">
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div>
                    <?php echo $__env->make('invoice-verification.components.status-badge', ['value' => $sheet->status], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <?php if($sheet->rejection_notes): ?>
                        <p class="text-danger mt-2 mb-0">Catatan penolakan: <?php echo e($sheet->rejection_notes); ?></p>
                    <?php endif; ?>
                </div>
                <button class="btn btn-outline-primary">Simpan Checklist</button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'PPA Verification Sheet'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/ppa-verification-sheets/edit.blade.php ENDPATH**/ ?>