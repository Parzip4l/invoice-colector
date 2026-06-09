<div class="table-responsive">
    <table class="table table-centered table-nowrap mb-0">
        <thead class="table-light">
            <tr>
                <th>Dokumen</th>
                <th>Sumber</th>
                <th>Versi</th>
                <th>Status</th>
                <th>Review / Catatan</th>
                <th>Uploaded</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?php echo e($document->document_label ?: $document->documentType?->name); ?></div>
                        <div class="text-muted small"><?php echo e($document->file_name); ?></div>
                        <?php if($document->document_information_json): ?>
                            <div class="text-muted small mt-1">
                                <?php echo e($document->document_information_json['document_number'] ?? '-'); ?>

                                <?php if(!empty($document->document_information_json['document_date'])): ?>
                                    · <?php echo e(\Illuminate\Support\Carbon::parse($document->document_information_json['document_date'])->format('d M Y')); ?>

                                <?php endif; ?>
                            </div>
                            <?php if(!empty($document->document_information_json['notes'])): ?>
                                <div class="text-muted small"><?php echo e($document->document_information_json['notes']); ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $document)): ?>
                            <div class="mt-1">
                                <a href="<?php echo e(route('invoice-verification.transaction-documents.preview', $document)); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-link px-0">Preview File</a>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e(str($document->source_actor->value ?? $document->source_actor)->replace('_', ' ')->title()); ?></td>
                    <td>v<?php echo e($document->version); ?></td>
                    <td><?php echo $__env->make('invoice-verification.components.status-badge', ['value' => $document->status], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></td>
                    <td>
                        <?php if($document->vendorReview): ?>
                            <div class="fw-semibold"><?php echo e(str($document->vendorReview->status->value)->replace('_', ' ')->title()); ?></div>
                            <div class="text-muted small"><?php echo e($document->vendorReview->notes ?: '-'); ?></div>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($document->uploaded_at?->format('d M Y H:i')); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada dokumen.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/components/document-table.blade.php ENDPATH**/ ?>