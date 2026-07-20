<?php
    $tableClass = isset($transaction) ? 'modern-table' : '';
?>

<?php if (! $__env->hasRenderedOnce('0f421489-c7f7-41cf-800c-0870a94c6662')): $__env->markAsRenderedOnce('0f421489-c7f7-41cf-800c-0870a94c6662'); ?>
    <style>
        .document-preview-action {
            min-height: 38px;
            padding: .45rem .8rem;
            font-weight: 700;
            border-width: 1.5px;
            box-shadow: 0 6px 16px rgba(226, 26, 26, .08);
        }

        .document-preview-action iconify-icon {
            font-size: 18px;
        }

        .document-table-actions {
            margin-top: .75rem;
        }
    </style>
<?php endif; ?>

<?php if($documents->isNotEmpty()): ?>
<div class="table-responsive">
    <table class="table table-centered table-nowrap mb-0 <?php echo e($tableClass); ?>">
        <thead class="<?php echo e(isset($transaction) ? '' : 'table-light'); ?>">
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
            <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                            <div class="document-table-actions">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary document-preview-action d-inline-flex align-items-center gap-2"
                                    data-file-preview-url="<?php echo e(route('invoice-verification.transaction-documents.preview', $document)); ?>"
                                    data-file-preview-title="<?php echo e($document->document_label ?: $document->documentType?->name); ?>"
                                >
                                    <iconify-icon icon="solar:eye-outline"></iconify-icon>
                                    <span>Lihat Dokumen</span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e(str($document->source_actor->value ?? $document->source_actor)->replace('_', ' ')->title()); ?></td>
                    <td><span class="badge bg-light text-dark border">v<?php echo e($document->version); ?></span></td>
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
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <div class="<?php echo e(isset($transaction) ? 'empty-state' : 'text-center text-muted py-4'); ?>">
        <?php if(isset($transaction)): ?>
            <span class="empty-icon mb-3"><iconify-icon icon="solar:folder-open-outline"></iconify-icon></span>
            <div class="fw-semibold">Belum ada dokumen.</div>
            <div class="text-muted small mt-1">Upload dokumen transaksi agar admin dapat melakukan review kelengkapan.</div>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('uploadDocuments', $transaction)): ?>
                <a href="<?php echo e(route('invoice-verification.transactions.documents.show', $transaction)); ?>" class="btn btn-sm btn-outline-primary mt-3">Upload Dokumen</a>
            <?php endif; ?>
        <?php else: ?>
            Belum ada dokumen.
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/components/document-table.blade.php ENDPATH**/ ?>