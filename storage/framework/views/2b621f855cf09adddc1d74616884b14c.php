<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Transactions', 'subtitle' => 'Create Transaction'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php
    $isVendor = auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::VENDOR);
?>

<form method="POST" action="<?php echo e(route('invoice-verification.transactions.store')); ?>" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-1">Informasi Transaksi</h5>
                    <p class="text-muted mb-0">Workflow awal akan dibentuk otomatis setelah transaksi dibuat.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Jenis Transaksi</label>
                            <select name="transaction_type_id" class="form-select" required>
                                <option value="">Pilih</option>
                                <?php $__currentLoopData = $transactionTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($type->id); ?>" <?php if((string) old('transaction_type_id') === (string) $type->id): echo 'selected'; endif; ?>><?php echo e($type->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vendor</label>
                            <?php if($isVendor): ?>
                                <input type="hidden" name="vendor_id" value="<?php echo e($linkedVendor?->id); ?>">
                                <input type="text" class="form-control" value="<?php echo e($linkedVendor?->name ?? 'Vendor belum terhubung'); ?>" readonly>
                            <?php else: ?>
                                <select name="vendor_id" class="form-select">
                                    <option value="">Tanpa vendor</option>
                                    <?php $__currentLoopData = $vendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($vendor->id); ?>" <?php if((string) old('vendor_id') === (string) $vendor->id): echo 'selected'; endif; ?>><?php echo e($vendor->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <?php if (! ($isVendor)): ?>
                            <div class="col-md-6">
                                <label class="form-label">Divisi</label>
                                <input type="hidden" name="division_id" value="<?php echo e(auth()->user()?->division_id); ?>">
                                <input type="text" class="form-control" value="<?php echo e($currentDivision?->name ?? '-'); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Departemen</label>
                                <select name="department_id" id="department_id" class="form-select" required>
                                    <option value="">Pilih departemen</option>
                                    <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($department->id); ?>" <?php if((string) old('department_id', auth()->user()?->department_id) === (string) $department->id): echo 'selected'; endif; ?>><?php echo e($department->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="form-label">Memo Request</label>
                            <select name="memo_request_id" id="memo_request_id" class="form-select" required>
                                <option value="">Pilih memo permohonan</option>
                                <?php $__currentLoopData = $memoRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $memo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($memo->id); ?>"
                                        data-department-id="<?php echo e($memo->department_id); ?>"
                                        <?php if((string) old('memo_request_id') === (string) $memo->id): echo 'selected'; endif; ?>>
                                        <?php echo e($memo->memo_number); ?> - <?php echo e($memo->subject); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <small class="text-muted">Memo dibuat dan diunggah lebih dulu oleh Admin Divisi dari menu Master Data.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Agreement Reference</label>
                            <select name="agreement_reference_id" id="agreement_reference_id" class="form-select">
                                <option value="">Pilih kontrak terdaftar</option>
                                <?php $__currentLoopData = $agreementReferences; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agreement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($agreement->id); ?>"
                                        data-department-id="<?php echo e($agreement->department_id); ?>"
                                        <?php if((string) old('agreement_reference_id') === (string) $agreement->id): echo 'selected'; endif; ?>>
                                        <?php echo e($agreement->contract_number); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <small class="text-muted">Untuk tagihan lanjutan, cukup pilih kontrak yang sudah pernah didaftarkan tanpa input ulang nomor kontrak.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" rows="4" name="description"><?php echo e(old('description')); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <?php if($isVendor): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-1">Upload Dokumen Tagihan</h5>
                        <p class="text-muted mb-0">Isi informasi dan unggah file tagihan untuk setiap dokumen PPA yang relevan.</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php $__currentLoopData = $ppaDocumentTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $documentType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-lg-6">
                                    <div class="border rounded-3 p-3 h-100">
                                        <h6 class="mb-3"><?php echo e($documentType->name); ?></h6>
                                        <input type="hidden" name="documents[<?php echo e($index); ?>][document_type_id]" value="<?php echo e($documentType->id); ?>">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Nomor Dokumen</label>
                                                <input type="text" class="form-control" name="documents[<?php echo e($index); ?>][document_information][document_number]" value="<?php echo e(old("documents.$index.document_information.document_number")); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Tanggal Dokumen</label>
                                                <input type="date" class="form-control" name="documents[<?php echo e($index); ?>][document_information][document_date]" value="<?php echo e(old("documents.$index.document_information.document_date")); ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Keterangan</label>
                                                <textarea class="form-control" rows="2" name="documents[<?php echo e($index); ?>][document_information][notes]"><?php echo e(old("documents.$index.document_information.notes")); ?></textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">File Dokumen</label>
                                                <input type="file" class="form-control" name="documents[<?php echo e($index); ?>][file]">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-1">Invoice Metadata</h5>
                    <p class="text-muted mb-0">Dipakai untuk validasi duplikasi invoice dan finalisasi register.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nomor Invoice</label>
                            <input type="text" class="form-control" name="invoice_number" value="<?php echo e(old('invoice_number')); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tanggal Invoice</label>
                            <input type="date" class="form-control" name="invoice_date" value="<?php echo e(old('invoice_date')); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tanggal Diterima</label>
                            <input type="date" class="form-control" name="received_date" value="<?php echo e(old('received_date')); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nomor Rekening</label>
                            <input type="text" class="form-control" name="account_number" value="<?php echo e(old('account_number')); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bank</label>
                            <input type="text" class="form-control" name="bank_name" value="<?php echo e(old('bank_name')); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nilai Invoice</label>
                            <input type="number" step="0.01" class="form-control" name="invoice_value" value="<?php echo e(old('invoice_value')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PPN</label>
                            <input type="number" step="0.01" class="form-control" name="ppn_value" value="<?php echo e(old('ppn_value')); ?>">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary w-100">Simpan Transaksi</button>
                </div>
            </div>
        </div>
    </div>
</form>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const departmentSelect = document.getElementById('department_id');
        const memoSelect = document.getElementById('memo_request_id');
        const agreementSelect = document.getElementById('agreement_reference_id');

        const syncSelectOptions = (selectElement, departmentId) => {
            if (!selectElement) {
                return;
            }

            let hasSelectedOption = false;

            Array.from(selectElement.options).forEach((option, index) => {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                const optionDepartmentId = option.dataset.departmentId;
                const isVisible = !departmentId || optionDepartmentId === departmentId;

                option.hidden = !isVisible;

                if (!isVisible && option.selected) {
                    option.selected = false;
                }

                if (isVisible && option.selected) {
                    hasSelectedOption = true;
                }
            });

            if (!hasSelectedOption) {
                selectElement.value = '';
            }
        };

        const applyDepartmentFilter = () => {
            const departmentId = departmentSelect?.value ?? '';

            syncSelectOptions(memoSelect, departmentId);
            syncSelectOptions(agreementSelect, departmentId);
        };

        departmentSelect?.addEventListener('change', applyDepartmentFilter);
        applyDepartmentFilter();
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Create Transaction'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/transactions/create.blade.php ENDPATH**/ ?>