<div class="app-sidebar">
     <div class="logo-box">
          <a href="{{ route('invoice-verification.dashboard') }}" class="logo-dark">
               <img src="/images/logo-sm.png" class="logo-sm" alt="SIGNAL icon">
               <img src="/images/logo.png" class="logo-lg" alt="SIGNAL logo">
          </a>

          <a href="{{ route('invoice-verification.dashboard') }}" class="logo-light">
               <img src="/images/logo-sm.png" class="logo-sm" alt="SIGNAL icon">
               <img src="/images/logo.png" class="logo-lg" alt="SIGNAL logo">
          </a>
     </div>

     <div class="scrollbar" data-simplebar>
          <ul class="navbar-nav" id="navbar-nav">
               <li class="menu-title">Invoice Verification</li>

               <li class="nav-item">
                    <a class="nav-link" href="{{ route('invoice-verification.dashboard') }}">
                         <span class="nav-icon">
                              <iconify-icon icon="solar:widget-2-outline"></iconify-icon>
                         </span>
                         <span class="nav-text"> Dashboard </span>
                    </a>
               </li>

               <li class="nav-item">
                    <a class="nav-link" href="{{ route('invoice-verification.manual-guide') }}">
                         <span class="nav-icon">
                              <iconify-icon icon="solar:notebook-bookmark-outline"></iconify-icon>
                         </span>
                         <span class="nav-text"> Panduan </span>
                    </a>
               </li>

               <li class="nav-item">
                    <a class="nav-link menu-arrow" href="#sidebarTransactions" data-bs-toggle="collapse" role="button"
                         aria-expanded="false" aria-controls="sidebarTransactions">
                         <span class="nav-icon">
                              <iconify-icon icon="solar:bill-list-outline"></iconify-icon>
                         </span>
                         <span class="nav-text"> Transaksi </span>
                    </a>
                    <div class="collapse show" id="sidebarTransactions">
                         <ul class="nav sub-navbar-nav">
                              <li class="sub-nav-item">
                                   <a class="sub-nav-link" href="{{ route('invoice-verification.transactions.index') }}">Daftar Transaksi</a>
                              </li>
                              @can('create', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class)
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="{{ route('invoice-verification.transactions.create') }}">Buat Transaksi</a>
                                   </li>
                              @endcan
                              @if(auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::VENDOR))
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="{{ route('invoice-verification.transactions.index', ['status' => \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value]) }}">Revisi</a>
                                   </li>
                              @endif
                              @if(auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::AKUNTANSI))
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="{{ route('invoice-verification.transactions.index', ['status' => \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::IN_REVIEW->value]) }}">Verifikasi Accounting</a>
                                   </li>
                              @endif
                         </ul>
                    </div>
               </li>

               @if(auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::ADMIN_DIVISI, \App\Modules\InvoiceVerification\Domain\Enums\RoleCode::AKUNTANSI))
                    <li class="nav-item">
                         <a class="nav-link menu-arrow" href="#sidebarFinalization" data-bs-toggle="collapse" role="button"
                              aria-expanded="false" aria-controls="sidebarFinalization">
                              <span class="nav-icon">
                                   <iconify-icon icon="solar:document-text-outline"></iconify-icon>
                              </span>
                              <span class="nav-text"> Data Penomoran </span>
                         </a>
                         <div class="collapse" id="sidebarFinalization">
                              <ul class="nav sub-navbar-nav">
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="{{ route('invoice-verification.numbering-registers.index') }}">Numbering Register</a>
                                   </li>
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="{{ route('invoice-verification.compiled-documents.index') }}">Dokumen Kompilasi</a>
                                   </li>
                                   <li class="sub-nav-item">
                                        <a class="sub-nav-link" href="{{ route('invoice-verification.archive.index') }}">Arsip</a>
                                   </li>
                              </ul>
                         </div>
                    </li>
               @endif

               @if(auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::ADMIN_DIVISI))
                    <li class="nav-item">
                         <a class="nav-link" href="{{ route('invoice-verification.master-data.index') }}">
                              <span class="nav-icon">
                                   <iconify-icon icon="solar:database-outline"></iconify-icon>
                              </span>
                              <span class="nav-text"> Master Data </span>
                         </a>
                    </li>
               @endif

               @unless(auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::VENDOR))
                    <li class="nav-item">
                         <a class="nav-link" href="{{ route('invoice-verification.audit-logs.index') }}">
                              <span class="nav-icon">
                                   <iconify-icon icon="solar:history-outline"></iconify-icon>
                              </span>
                              <span class="nav-text"> Log Audit </span>
                         </a>
                    </li>
               @endunless
          </ul>
     </div>
</div>
