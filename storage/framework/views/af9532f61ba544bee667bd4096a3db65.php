<div class="app-sidebar">
     <div class="logo-box">
          <a href="<?php echo e(route('invoice-verification.dashboard')); ?>" class="logo-dark">
               <img src="/images/logogram.png" class="logo-sm" alt="logo sm" style="height: 40px!important">
               <img src="/images/logo.png" class="logo-lg" alt="logo dark" style="height: 40px!important">
          </a>

          <a href="<?php echo e(route('invoice-verification.dashboard')); ?>" class="logo-light">
               <img src="/images/logogram.png" class="logo-sm" alt="logo sm" style="height: 40px">
               <img src="/images/logo.png" class="logo-xl" alt="logo light" style="height: 40px!important">
          </a>
     </div>

     <div class="scrollbar" data-simplebar>
          <ul class="navbar-nav" id="navbar-nav">
               <li class="menu-title">Sistem Verifikasi</li>

               <li class="nav-item">
                    <a class="nav-link" href="<?php echo e(route('invoice-verification.dashboard')); ?>">
                         <span class="nav-icon">
                              <iconify-icon icon="solar:widget-2-outline"></iconify-icon>
                         </span>
                         <span class="nav-text"> Dashboard </span>
                    </a>
               </li>

               <li class="nav-item">
                    <a class="nav-link menu-arrow" href="#sidebarTransactions" data-bs-toggle="collapse" role="button"
                         aria-expanded="false" aria-controls="sidebarTransactions">
                         <span class="nav-icon">
                              <iconify-icon icon="solar:bill-list-outline"></iconify-icon>
                         </span>
                         <span class="nav-text"> Transactions </span>
                    </a>
                    <div class="collapse show" id="sidebarTransactions">
                         <ul class="nav sub-navbar-nav">
                              <li class="sub-nav-item">
                                   <a class="sub-nav-link" href="<?php echo e(route('invoice-verification.transactions.index')); ?>">Daftar Transaksi</a>
                              </li>
                              <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class)): ?>
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="<?php echo e(route('invoice-verification.transactions.create')); ?>">Buat Transaksi</a>
                                   </li>
                              <?php endif; ?>
                              <?php if(auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::VENDOR)): ?>
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="<?php echo e(route('invoice-verification.transactions.index', ['status' => 'REVISION_IN_PROGRESS'])); ?>">Revisi</a>
                                   </li>
                              <?php endif; ?>
                              <?php if(auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::ADMIN_DIVISI)): ?>
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="<?php echo e(route('invoice-verification.vendor-reviews.index')); ?>">Admin Review</a>
                                   </li>
                              <?php endif; ?>
                              <?php if(auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::KEPALA_DEPARTEMEN)): ?>
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="<?php echo e(route('invoice-verification.kadep-review.index')); ?>">Kadep Review</a>
                                   </li>
                              <?php endif; ?>
                              <?php if(auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::KEPALA_DIVISI)): ?>
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="<?php echo e(route('invoice-verification.kadiv-review.index')); ?>">Kadiv Review</a>
                                   </li>
                              <?php endif; ?>
                              <?php if(auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::AKUNTANSI)): ?>
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="<?php echo e(route('invoice-verification.transactions.index', ['status' => 'ACCOUNTING_VERIFICATION'])); ?>">Verification</a>
                                   </li>
                              <?php endif; ?>
                         </ul>
                    </div>
               </li>

               <?php if(auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::ADMIN_DIVISI, \App\Modules\InvoiceVerification\Domain\Enums\RoleCode::AKUNTANSI)): ?>
                    <li class="nav-item">
                         <a class="nav-link menu-arrow" href="#sidebarFinalization" data-bs-toggle="collapse" role="button"
                              aria-expanded="false" aria-controls="sidebarFinalization">
                              <span class="nav-icon">
                                   <iconify-icon icon="solar:document-text-outline"></iconify-icon>
                              </span>
                              <span class="nav-text"> Finalization </span>
                         </a>
                         <div class="collapse" id="sidebarFinalization">
                              <ul class="nav sub-navbar-nav">
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="<?php echo e(route('invoice-verification.numbering-registers.index')); ?>">Numbering Register</a>
                                   </li>
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="<?php echo e(route('invoice-verification.compiled-documents.index')); ?>">Compiled Documents</a>
                                   </li>
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="<?php echo e(route('invoice-verification.archive.index')); ?>">Archive</a>
                                   </li>
                              </ul>
                         </div>
                    </li>
               <?php endif; ?>

               <?php if(auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::ADMIN_DIVISI)): ?>
                    <li class="nav-item">
                         <a class="nav-link" href="<?php echo e(route('invoice-verification.master-data.index')); ?>">
                              <span class="nav-icon">
                                   <iconify-icon icon="solar:database-outline"></iconify-icon>
                              </span>
                              <span class="nav-text"> Master Data </span>
                         </a>
                    </li>
               <?php endif; ?>

               <?php if (! (auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::VENDOR))): ?>
                    <li class="nav-item">
                         <a class="nav-link" href="<?php echo e(route('invoice-verification.audit-logs.index')); ?>">
                              <span class="nav-icon">
                                   <iconify-icon icon="solar:history-outline"></iconify-icon>
                              </span>
                              <span class="nav-text"> Audit Logs </span>
                         </a>
                    </li>
               <?php endif; ?>
          </ul>
     </div>
</div>
<?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/layouts/partials/sidebar.blade.php ENDPATH**/ ?>