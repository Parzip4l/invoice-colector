<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Admin Review', 'subtitle' => 'Pengecekan Tagihan Vendor'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transaksi</th>
                        <th>Dokumen</th>
                        <th>Vendor</th>
                        <th>Versi</th>
                        <th>Uploaded</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $pendingDocuments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo e($document->transaction?->registration_number); ?></div>
                                <div class="text-muted small"><?php echo e($document->transaction?->title); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo e($document->document_label ?: $document->documentType?->name); ?></div>
                                <?php if($document->document_information_json): ?>
                                    <div class="text-muted small">
                                        <?php echo e($document->document_information_json['document_number'] ?? '-'); ?>

                                        <?php if(!empty($document->document_information_json['document_date'])): ?>
                                            · <?php echo e(\Illuminate\Support\Carbon::parse($document->document_information_json['document_date'])->format('d M Y')); ?>

                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($document->transaction?->vendor?->name ?? '-'); ?></td>
                            <td>v<?php echo e($document->version); ?></td>
                            <td><?php echo e($document->uploaded_at?->format('d M Y H:i')); ?></td>
                            <td class="text-end">
                                <a
                                    href="<?php echo e(route('invoice-verification.transaction-documents.preview', $document)); ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    Preview
                                </a>
                                <form method="POST" action="<?php echo e(route('invoice-verification.vendor-reviews.update', $document)); ?>" class="d-inline-flex gap-2 align-items-start">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PUT'); ?>
                                    <input type="hidden" name="status" value="ACCEPTED">
                                    <button class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form method="POST" action="<?php echo e(route('invoice-verification.vendor-reviews.update', $document)); ?>" class="d-inline-flex gap-2 align-items-start ms-1">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PUT'); ?>
                                    <input type="hidden" name="status" value="REVISION_REQUIRED">
                                    <textarea
                                        name="notes"
                                        class="form-control form-control-sm"
                                        rows="2"
                                        required
                                        placeholder="Keterangan revisi untuk vendor"
                                        style="min-width: 220px;"
                                    ></textarea>
                                    <button class="btn btn-sm btn-outline-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Tidak ada dokumen vendor yang menunggu pengecekan hasil pekerjaan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <?php echo e($pendingDocuments->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Admin Review'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/vendor-reviews/index.blade.php ENDPATH**/ ?>