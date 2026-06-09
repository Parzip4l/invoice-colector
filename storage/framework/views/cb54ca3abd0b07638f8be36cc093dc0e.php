<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Transactions', 'subtitle' => 'Transaction Detail'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php
    $isVendorRevisionView = auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::VENDOR)
        && $transaction->status?->value === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::REVISION_IN_PROGRESS->value;
?>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <div>
                        <h4 class="mb-1"><?php echo e($transaction->title); ?></h4>
                        <p class="text-muted mb-2"><?php echo e($transaction->registration_number); ?> · <?php echo e($transaction->transactionType?->name); ?></p>
                        <div class="d-flex flex-wrap gap-2">
                            <?php echo $__env->make('invoice-verification.components.status-badge', ['value' => $transaction->status], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            <span class="badge bg-light text-dark"><?php echo e($transaction->current_step?->label()); ?></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('uploadDocuments', $transaction)): ?>
                            <a href="<?php echo e(route('invoice-verification.transactions.documents.show', $transaction)); ?>" class="btn btn-outline-primary">Upload Dokumen</a>
                        <?php endif; ?>
                        <?php if(
                            auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::ADMIN_DIVISI)
                            && $transaction->status?->value === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ADMIN_GENERATE_DOCUMENTS->value
                        ): ?>
                            <form method="POST" action="<?php echo e(route('invoice-verification.transactions.admin-documents.generate', $transaction)); ?>">
                                <?php echo csrf_field(); ?>
                                <button class="btn btn-primary">Generate Lembar PPA & Verifikasi</button>
                            </form>
                        <?php endif; ?>
                        <?php if($transaction->isPpa() && ! auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::VENDOR)): ?>
                            <span class="btn btn-outline-secondary disabled">Upload PPA Dilakukan Vendor</span>
                        <?php endif; ?>
                        <?php if($transaction->isPpa() && $transaction->ppaVerificationSheet): ?>
                            <a href="<?php echo e(route('invoice-verification.transactions.ppa-verification-sheets.edit', $transaction)); ?>" class="btn btn-outline-secondary">Lembar Verifikasi PPA</a>
                        <?php endif; ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('verifyAccounting', $transaction)): ?>
                            <a href="<?php echo e(route('invoice-verification.transactions.accounting-verifications.edit', $transaction)); ?>" class="btn btn-primary">Verifikasi Akuntansi</a>
                        <?php endif; ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('processFinance', $transaction)): ?>
                            <?php if($transaction->status?->value === 'FINANCE_PROCESS'): ?>
                                <a href="<?php echo e(route('invoice-verification.finance.index')); ?>" class="btn btn-primary">Proses Finance</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Vendor</small>
                            <div class="fw-semibold"><?php echo e($transaction->vendor?->name ?? '-'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Divisi / Departemen</small>
                            <div class="fw-semibold"><?php echo e($transaction->division?->name); ?></div>
                            <div class="text-muted small"><?php echo e($transaction->department?->name); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Memo / Kontrak</small>
                            <div class="fw-semibold"><?php echo e($transaction->memoRequest?->memo_number ?? $transaction->invoiceMetadata?->memo_number ?? '-'); ?></div>
                            <div class="text-muted small"><?php echo e($transaction->agreementReference?->contract_number ?? $transaction->contract_number ?? $transaction->invoiceMetadata?->contract_number ?? '-'); ?></div>
                            <?php if($transaction->memoRequest?->file_path): ?>
                                <a href="<?php echo e(route('invoice-verification.master-data.memo-requests.download', $transaction->memoRequest)); ?>" class="btn btn-sm btn-link px-0 mt-2">Lihat File Memo</a>
                            <?php endif; ?>
                            <?php if($transaction->agreementReference?->file_path): ?>
                                <a href="<?php echo e(route('invoice-verification.master-data.agreement-references.download', $transaction->agreementReference)); ?>" class="btn btn-sm btn-link px-0 d-block">Lihat File Kontrak</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Invoice Metadata</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo e(route('invoice-verification.transactions.invoice-metadata.update', $transaction)); ?>" class="row g-3">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="col-12">
                        <label class="form-label">Nomor Invoice</label>
                        <input type="text" class="form-control" name="invoice_number" value="<?php echo e(old('invoice_number', $transaction->invoiceMetadata?->invoice_number)); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tanggal Invoice</label>
                        <input type="date" class="form-control" name="invoice_date" value="<?php echo e(old('invoice_date', optional($transaction->invoiceMetadata?->invoice_date)->format('Y-m-d'))); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Bank</label>
                        <input type="text" class="form-control" name="bank_name" value="<?php echo e(old('bank_name', $transaction->invoiceMetadata?->bank_name)); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nilai Invoice</label>
                        <input type="number" class="form-control" step="0.01" name="invoice_value" value="<?php echo e(old('invoice_value', $transaction->invoiceMetadata?->invoice_value)); ?>">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-outline-primary w-100">Perbarui Metadata</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="<?php echo e($isVendorRevisionView ? 'col-12' : 'col-xl-8'); ?>">
        <?php if (! ($isVendorRevisionView)): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Generated Initial Documents</h5>
                    <span class="text-muted small">Dokumen yang dikontrol sistem dan approval terkait.</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dokumen</th>
                                    <th>Nomor</th>
                                    <th>Status Generate</th>
                                    <th>Approval Mode</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $transaction->generatedDocuments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $generatedDocument): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e(str($generatedDocument->document_code)->replace('_', ' ')->title()); ?></td>
                                        <td><?php echo e($generatedDocument->document_number ?? '-'); ?></td>
                                        <td><?php echo e($generatedDocument->generation_status->value); ?></td>
                                        <td><?php echo e($generatedDocument->approval_mode->value); ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <a href="<?php echo e(route('invoice-verification.generated-documents.preview', $generatedDocument)); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Preview File</a>
                                                <a href="<?php echo e(route('invoice-verification.generated-documents.show', $generatedDocument)); ?>" class="btn btn-sm btn-outline-secondary">Detail</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada generated document.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if($transaction->ppaVerificationSheet?->file_path): ?>
                        <div class="border rounded-3 p-3 mt-3 d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <div class="fw-semibold">Lembar Checklist PPA</div>
                                <div class="text-muted small"><?php echo e($transaction->ppaVerificationSheet->file_name ?? 'Checklist PPA'); ?></div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="<?php echo e(route('invoice-verification.transactions.ppa-verification-sheets.preview', $transaction)); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Preview File</a>
                                <a href="<?php echo e(route('invoice-verification.transactions.ppa-verification-sheets.edit', $transaction)); ?>" class="btn btn-sm btn-outline-secondary">Detail Checklist</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Dokumen Transaksi</h5>
            </div>
            <div class="card-body">
                <?php echo $__env->make('invoice-verification.components.document-table', ['documents' => $transaction->latestDocuments], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>

        <?php if($mismatches && ! $isVendorRevisionView): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Mismatch Checklist PPA</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dokumen</th>
                                    <th>Checklist</th>
                                    <th>File Aktual</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $mismatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($item['document_name']); ?></td>
                                        <td><?php echo e($item['checklist_status']); ?></td>
                                        <td><?php echo e($item['actual_available'] ? 'AVAILABLE' : 'MISSING'); ?></td>
                                        <td>
                                            <span class="badge <?php echo e($item['is_mismatch'] ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success'); ?>">
                                                <?php echo e($item['is_mismatch'] ? 'Mismatch' : 'Match'); ?>

                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (! ($isVendorRevisionView)): ?>
        <div class="col-xl-4">
            <?php echo $__env->make('invoice-verification.components.timeline', ['histories' => $transaction->statusHistory], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Audit Trail</h5>
                </div>
                <div class="card-body">
                    <?php $__empty_1 = true; $__currentLoopData = $auditLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="<?php echo e(!$loop->last ? 'pb-3 mb-3 border-bottom' : ''); ?>">
                            <div class="fw-semibold"><?php echo e(str($log->action)->replace('_', ' ')->title()); ?></div>
                            <div class="text-muted small"><?php echo e($log->module); ?></div>
                            <small class="text-muted"><?php echo e($log->created_at?->format('d M Y H:i')); ?></small>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-muted mb-0">Belum ada audit log.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Transaction Detail'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/transactions/show.blade.php ENDPATH**/ ?>