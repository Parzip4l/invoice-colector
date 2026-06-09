<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Finance Queue', 'subtitle' => 'Register, Numbering, dan Arsip Final'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-1">Transaksi Siap Diproses Finance</h5>
        <p class="text-muted mb-0">Dokumen sudah lolos verifikasi akuntansi, register sudah dibuat, dan bundle final siap ditutup oleh finance.</p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transaksi</th>
                        <th>Vendor</th>
                        <th>Register</th>
                        <th>Compiled File</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo e($transaction->registration_number); ?></div>
                                <div class="text-muted small"><?php echo e($transaction->title); ?></div>
                            </td>
                            <td><?php echo e($transaction->vendor?->name ?? '-'); ?></td>
                            <td><?php echo e($transaction->numberingRegister?->register_number ?? '-'); ?></td>
                            <td><?php echo e($transaction->compiledDocument?->compiled_file_name ?? '-'); ?></td>
                            <td class="text-end">
                                <form method="POST" action="<?php echo e(route('invoice-verification.finance.update', $transaction)); ?>" class="d-inline-flex">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PUT'); ?>
                                    <input type="hidden" name="notes" value="Finance menyelesaikan register, numbering, dan arsip final sesuai workflow pembayaran.">
                                    <button class="btn btn-sm btn-success">Selesaikan Finance</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Tidak ada transaksi yang sedang menunggu proses finance.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <?php echo e($transactions->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Finance Queue'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/finance/index.blade.php ENDPATH**/ ?>