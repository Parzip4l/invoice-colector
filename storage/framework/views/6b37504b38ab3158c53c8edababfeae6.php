<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Accounting Verification', 'subtitle' => 'Document Checks'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-1"><?php echo e($transaction->registration_number); ?></h5>
        <p class="text-muted mb-0">Accounting memverifikasi Administration dan Invoicing secara terpisah.</p>
    </div>
    <div class="card-body">
        <?php
            $selectedAdministrationStatus = old('administration_status', 'VALID');
        ?>
        <form method="POST" action="<?php echo e(route('invoice-verification.transactions.accounting-verifications.update', $transaction)); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <div class="border rounded-3 p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                    <div>
                        <h5 class="mb-1">Administration</h5>
                        <p class="text-muted mb-0">Berisi Lembar PPA dan Lembar Checklist yang digenerate setelah review Admin User.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap" data-status-toggle-group>
                        <input type="hidden" name="administration_status" value="<?php echo e($selectedAdministrationStatus); ?>" data-status-toggle-input>
                        <button
                            type="button"
                            class="btn <?php echo e($selectedAdministrationStatus === 'VALID' ? 'btn-success' : 'btn-outline-success'); ?>"
                            data-status-toggle-option
                            data-status-value="VALID"
                            data-status-variant="success"
                        >
                            Approve
                        </button>
                        <button
                            type="button"
                            class="btn <?php echo e($selectedAdministrationStatus === 'REVISION_REQUIRED' ? 'btn-danger' : 'btn-outline-danger'); ?>"
                            data-status-toggle-option
                            data-status-value="REVISION_REQUIRED"
                            data-status-variant="danger"
                        >
                            Reject
                        </button>
                    </div>
                </div>
                <div class="row g-3">
                    <?php $__empty_1 = true; $__currentLoopData = $transaction->generatedDocuments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $generatedDocument): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold"><?php echo e(str($generatedDocument->document_code)->replace('_', ' ')->title()); ?></div>
                                <div class="text-muted small"><?php echo e($generatedDocument->document_number ?? '-'); ?></div>
                                <div class="d-flex gap-2 flex-wrap mt-2">
                                    <a href="<?php echo e(route('invoice-verification.generated-documents.preview', $generatedDocument)); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Preview File</a>
                                    <a href="<?php echo e(route('invoice-verification.generated-documents.show', $generatedDocument)); ?>" class="btn btn-sm btn-outline-secondary">Detail</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="col-12 text-muted">Belum ada Lembar PPA yang digenerate.</div>
                    <?php endif; ?>

                    <?php if($transaction->ppaVerificationSheet?->file_path): ?>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold">Lembar Checklist PPA</div>
                                <div class="text-muted small"><?php echo e($transaction->ppaVerificationSheet->file_name ?? 'Checklist PPA'); ?></div>
                                <div class="d-flex gap-2 flex-wrap mt-2">
                                    <a href="<?php echo e(route('invoice-verification.transactions.ppa-verification-sheets.preview', $transaction)); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Preview File</a>
                                    <a href="<?php echo e(route('invoice-verification.transactions.ppa-verification-sheets.edit', $transaction)); ?>" class="btn btn-sm btn-outline-secondary">Detail Checklist</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <label class="form-label mt-3">Catatan Administration</label>
                <textarea class="form-control" rows="2" name="administration_notes" placeholder="Wajib diisi jika Administration direject"><?php echo e(old('administration_notes')); ?></textarea>
            </div>

            <div class="border rounded-3 p-3">
                <h5 class="mb-1">Invoicing</h5>
                <p class="text-muted mb-3">Berisi seluruh dokumen tagihan yang diinput dan diupload vendor.</p>
            <div class="table-responsive">
                <table class="table table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Dokumen</th>
                            <th>Versi</th>
                            <th>Preview</th>
                            <th>Status Verifikasi</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $verification->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $selectedItemStatus = old("items.$index.status", $item->status?->value ?? 'VALID');
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo e($item->transactionDocument?->document_label ?: $item->transactionDocument?->documentType?->name); ?></div>
                                    <div class="text-muted small"><?php echo e($item->transactionDocument?->file_name); ?></div>
                                    <input type="hidden" name="items[<?php echo e($index); ?>][transaction_document_id]" value="<?php echo e($item->transaction_document_id); ?>">
                                </td>
                                <td>v<?php echo e($item->transactionDocument?->version); ?></td>
                                <td>
                                    <?php if($item->transactionDocument): ?>
                                        <a href="<?php echo e(route('invoice-verification.transaction-documents.preview', $item->transactionDocument)); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Preview File</a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 flex-wrap" data-status-toggle-group>
                                        <input type="hidden" name="items[<?php echo e($index); ?>][status]" value="<?php echo e($selectedItemStatus); ?>" data-status-toggle-input>
                                        <button
                                            type="button"
                                            class="btn btn-sm <?php echo e($selectedItemStatus === 'VALID' ? 'btn-success' : 'btn-outline-success'); ?>"
                                            data-status-toggle-option
                                            data-status-value="VALID"
                                            data-status-variant="success"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm <?php echo e($selectedItemStatus === 'REVISION_REQUIRED' ? 'btn-danger' : 'btn-outline-danger'); ?>"
                                            data-status-toggle-option
                                            data-status-value="REVISION_REQUIRED"
                                            data-status-variant="danger"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <textarea
                                        name="items[<?php echo e($index); ?>][notes]"
                                        class="form-control"
                                        rows="2"
                                        placeholder="Wajib diisi jika dokumen direject"
                                    ><?php echo e($item->notes); ?></textarea>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-8">
                    <label class="form-label">Catatan Umum</label>
                    <textarea class="form-control" rows="3" name="notes"><?php echo e(old('notes', $verification->notes)); ?></textarea>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Simpan Verifikasi Akuntansi</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleGroups = document.querySelectorAll('[data-status-toggle-group]');

        const updateButtonState = (button, isActive) => {
            const variant = button.dataset.statusVariant;
            button.classList.toggle(`btn-${variant}`, isActive);
            button.classList.toggle(`btn-outline-${variant}`, !isActive);
        };

        toggleGroups.forEach((group) => {
            const input = group.querySelector('[data-status-toggle-input]');
            const buttons = group.querySelectorAll('[data-status-toggle-option]');

            const applySelection = (value) => {
                if (!input) {
                    return;
                }

                input.value = value;

                buttons.forEach((button) => {
                    updateButtonState(button, button.dataset.statusValue === value);
                });
            };

            buttons.forEach((button) => {
                button.addEventListener('click', function () {
                    applySelection(this.dataset.statusValue);
                });
            });

            applySelection(input?.value ?? 'VALID');
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Accounting Verification'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/accounting-verifications/edit.blade.php ENDPATH**/ ?>