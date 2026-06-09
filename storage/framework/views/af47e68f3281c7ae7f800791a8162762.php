<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Master Data', 'subtitle' => 'Reference Tables'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">LDAP Sync Ready</h5>
                <form method="POST" action="<?php echo e(route('invoice-verification.master-data.ldap-sync')); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="btn btn-sm btn-outline-primary">Sync Placeholder</button>
                </form>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">Users, departments, dan divisions sudah disiapkan untuk sinkronisasi LDAP melalui service contract.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Tambah Vendor</h5></div>
            <div class="card-body">
                <form method="POST" action="<?php echo e(route('invoice-verification.master-data.vendors.store')); ?>" class="row g-3">
                    <?php echo csrf_field(); ?>
                    <div class="col-12">
                        <label class="form-label">Vendor Name</label>
                        <input class="form-control" name="name" value="<?php echo e(old('name')); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">NPWP</label>
                        <input class="form-control" name="npwp" value="<?php echo e(old('npwp')); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Bank Name</label>
                        <input class="form-control" name="bank_name" value="<?php echo e(old('bank_name')); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nomor Rekening</label>
                        <input class="form-control" name="default_account_number" value="<?php echo e(old('default_account_number')); ?>">
                    </div>
                    <div class="col-12"><button class="btn btn-primary w-100">Simpan Vendor</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Reference Tables</h5></div>
            <div class="card-body">
                <ul class="nav nav-tabs nav-bordered mb-3">
                    <li class="nav-item"><a href="#vendors" data-bs-toggle="tab" class="nav-link active">Vendors</a></li>
                    <li class="nav-item"><a href="#memo" data-bs-toggle="tab" class="nav-link">Memo</a></li>
                    <li class="nav-item"><a href="#agreements" data-bs-toggle="tab" class="nav-link">Agreements</a></li>
                    <li class="nav-item"><a href="#templates" data-bs-toggle="tab" class="nav-link">Templates</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane show active" id="vendors">
                        <div class="table-responsive">
                            <table class="table table-centered mb-0">
                                <thead class="table-light"><tr><th>Vendor Name</th><th>NPWP</th><th>Bank Name</th><th>Nomor Rekening</th></tr></thead>
                                <tbody><?php $__currentLoopData = $vendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr><td><?php echo e($vendor->name); ?></td><td><?php echo e($vendor->npwp); ?></td><td><?php echo e($vendor->defaultBank?->name ?? '-'); ?></td><td><?php echo e($vendor->default_account_number ?? '-'); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="memo">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('createMemoRequest', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class)): ?>
                            <div class="mb-3">
                                <form method="POST" action="<?php echo e(route('invoice-verification.master-data.memo-requests.store')); ?>" class="row g-2" enctype="multipart/form-data">
                                    <?php echo csrf_field(); ?>
                                    <div class="col-md-3">
                                        <label class="form-label">Nomor Memo</label>
                                        <input class="form-control" name="memo_number" placeholder="Masukkan nomor memo" value="<?php echo e(old('memo_number')); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tanggal Memo</label>
                                        <input class="form-control" type="date" name="memo_date" value="<?php echo e(old('memo_date')); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Perihal</label>
                                        <input class="form-control" name="subject" placeholder="Masukkan perihal memo" value="<?php echo e(old('subject')); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Divisi</label>
                                        <select class="form-select" name="division_id">
                                            <?php $__currentLoopData = $divisions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $division): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($division->id); ?>" <?php if(old('division_id', auth()->user()?->division_id) === $division->id): echo 'selected'; endif; ?>><?php echo e($division->name); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Departemen</label>
                                        <select class="form-select" name="department_id">
                                            <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($department->id); ?>" <?php if(old('department_id', auth()->user()?->department_id) === $department->id): echo 'selected'; endif; ?>><?php echo e($department->name); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">File Memo</label>
                                        <input class="form-control" type="file" name="memo_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-primary w-100">Simpan Memo</button>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Keterangan Tambahan</label>
                                        <textarea class="form-control" rows="2" name="description" placeholder="Keterangan tambahan memo"><?php echo e(old('description')); ?></textarea>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light border mb-3">
                                Form upload memo hanya tersedia untuk Admin Divisi. Akuntansi tetap dapat melihat daftar memo yang sudah terdaftar.
                            </div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-centered mb-0">
                                <thead class="table-light"><tr><th>Memo Number</th><th>Date</th><th>Subject</th><th>File</th><th>Uploader</th></tr></thead>
                                <tbody><?php $__currentLoopData = $memoRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $memo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr><td><?php echo e($memo->memo_number); ?></td><td><?php echo e($memo->memo_date?->format('d M Y')); ?></td><td><?php echo e($memo->subject); ?></td><td><?php if($memo->file_path): ?><a href="<?php echo e(route('invoice-verification.master-data.memo-requests.download', $memo)); ?>" class="btn btn-sm btn-outline-primary"><?php echo e($memo->file_name ?? 'Download'); ?></a><?php else: ?><span class="text-muted">Belum ada file</span><?php endif; ?></td><td><?php echo e($memo->creator?->name ?? '-'); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="agreements">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('createAgreementReference', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class)): ?>
                            <div class="mb-3">
                                <form method="POST" action="<?php echo e(route('invoice-verification.master-data.agreement-references.store')); ?>" class="row g-2" enctype="multipart/form-data">
                                    <?php echo csrf_field(); ?>
                                    <div class="col-md-3">
                                        <label class="form-label">Vendor</label>
                                        <select class="form-select" name="vendor_id">
                                            <option value="">Pilih vendor</option>
                                            <?php $__currentLoopData = $vendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($vendor->id); ?>" <?php if(old('vendor_id') === $vendor->id): echo 'selected'; endif; ?>><?php echo e($vendor->name); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Nomor Kontrak</label>
                                        <input class="form-control" name="contract_number" value="<?php echo e(old('contract_number')); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Nilai Kontrak</label>
                                        <input class="form-control" name="contract_value" value="<?php echo e(old('contract_value')); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tanggal Berlaku</label>
                                        <input class="form-control" type="date" name="effective_date" value="<?php echo e(old('effective_date')); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tanggal Berakhir</label>
                                        <input class="form-control" type="date" name="expired_at" value="<?php echo e(old('expired_at')); ?>">
                                    </div>
                                    <div class="col-md-9">
                                        <label class="form-label">File Agreement</label>
                                        <input class="form-control" type="file" name="agreement_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                    </div>
                                    <div class="col-md-3"><button class="btn btn-primary w-100">Simpan Kontrak</button></div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light border mb-3">
                                Form upload kontrak hanya tersedia untuk Admin Divisi. Data tetap dapat dipilih ulang pada transaksi berikutnya.
                            </div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-centered mb-0">
                                <thead class="table-light"><tr><th>Nomor Kontrak</th><th>Vendor</th><th>Nilai Kontrak</th><th>File</th><th>Unit</th></tr></thead>
                                <tbody><?php $__currentLoopData = $agreementReferences; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agreement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr><td><?php echo e($agreement->contract_number); ?></td><td><?php echo e($agreement->vendor?->name ?? '-'); ?></td><td><?php echo e(number_format((float) $agreement->contract_value, 2, ',', '.')); ?></td><td><?php if($agreement->file_path): ?><a href="<?php echo e(route('invoice-verification.master-data.agreement-references.download', $agreement)); ?>" class="btn btn-sm btn-outline-primary"><?php echo e($agreement->file_name ?? 'Download'); ?></a><?php else: ?><span class="text-muted">Belum ada file</span><?php endif; ?></td><td><?php echo e($agreement->division?->name ?? '-'); ?> / <?php echo e($agreement->department?->name ?? '-'); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="templates">
                        <div class="mb-3">
                            <form method="POST" action="<?php echo e(route('invoice-verification.master-data.template-references.store')); ?>" class="row g-2">
                                <?php echo csrf_field(); ?>
                                <div class="col-md-3">
                                    <label class="form-label">Template Code</label>
                                    <input class="form-control" name="code" placeholder="Masukkan kode template">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Template Name</label>
                                    <input class="form-control" name="name" placeholder="Masukkan nama template">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Template Type</label>
                                    <select class="form-select" name="template_type">
                                        <option value="GENERATED_DOCUMENT">Generated Document</option>
                                        <option value="FINAL_COMPILATION_ORDER">Final Compilation Order</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Transaction Type</label>
                                    <select class="form-select" name="transaction_type_id">
                                        <option value="">Type</option>
                                        <?php $__currentLoopData = $transactionTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($type->id); ?>"><?php echo e($type->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                                <div class="col-md-1"><button class="btn btn-primary w-100">+</button></div>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-centered mb-0">
                                <thead class="table-light"><tr><th>Code</th><th>Name</th><th>Type</th><th>Document Code</th></tr></thead>
                                <tbody><?php $__currentLoopData = $templateReferences; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr><td><?php echo e($template->code); ?></td><td><?php echo e($template->name); ?></td><td><?php echo e($template->template_type->value); ?></td><td><?php echo e($template->document_code ?? '-'); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Master Data'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/master-data/index.blade.php ENDPATH**/ ?>