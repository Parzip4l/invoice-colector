@php
     use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
     use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
     use App\Modules\InvoiceVerification\Domain\Models\Transaction;

     $user = auth()->user();
     $isVendor = $user?->hasRole(RoleCode::VENDOR);
     $isAccounting = $user?->hasRole(RoleCode::AKUNTANSI);
     $canManageFinalization = $user?->hasRole(RoleCode::ADMIN_DIVISI, RoleCode::AKUNTANSI, RoleCode::FINANCE);
     $canViewApprovals = $user?->can('viewAny', \App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction::class);
     $isRevisionList = request()->routeIs('invoice-verification.transactions.index')
          && request('status') === TransactionStatus::NOT_APPROVED->value;
     $isAccountingReview = request()->routeIs('invoice-verification.transactions.index')
          && request('status') === TransactionStatus::IN_REVIEW->value;
     $isTransactionList = request()->routeIs('invoice-verification.transactions.index')
          && ! $isRevisionList
          && ! $isAccountingReview;
@endphp

<aside class="app-sidebar">
     <div class="logo-box">
          <a href="{{ route('invoice-verification.dashboard') }}" class="logo-dark" aria-label="Buka Dashboard SIGNAL">
               <span class="logo-sm" aria-hidden="true"></span>
               <img src="/images/lrt-jakarta-logo.png?v=20261020-1" class="logo-lg" alt="LRT Jakarta">
          </a>

          <a href="{{ route('invoice-verification.dashboard') }}" class="logo-light" aria-label="Buka Dashboard SIGNAL">
               <span class="logo-sm" aria-hidden="true"></span>
               <img src="/images/lrt-jakarta-logo.png?v=20261020-1" class="logo-lg" alt="LRT Jakarta">
          </a>
     </div>

     <div class="scrollbar" data-simplebar>
          <nav aria-label="Navigasi utama">
               <ul class="navbar-nav" id="navbar-nav">
                    <li class="menu-title"><span>Navigasi</span></li>

                    <li class="nav-item">
                         <a class="nav-link @if(request()->routeIs('invoice-verification.dashboard')) active @endif" href="{{ route('invoice-verification.dashboard') }}">
                              <span class="nav-icon"><iconify-icon icon="solar:chart-square-outline"></iconify-icon></span>
                              <span class="nav-text">Dashboard</span>
                         </a>
                    </li>

                    <li class="nav-item">
                         <a class="nav-link @if(request()->routeIs('invoice-verification.manual-guide')) active @endif" href="{{ route('invoice-verification.manual-guide') }}">
                              <span class="nav-icon"><iconify-icon icon="solar:notebook-minimalistic-outline"></iconify-icon></span>
                              <span class="nav-text">Panduan</span>
                         </a>
                    </li>

                    <li class="menu-title"><span>Transaksi</span></li>

                    <li class="nav-item">
                         <a class="nav-link @if($isTransactionList) active @endif" href="{{ route('invoice-verification.transactions.index') }}">
                              <span class="nav-icon"><iconify-icon icon="solar:bill-list-outline"></iconify-icon></span>
                              <span class="nav-text">Daftar Transaksi</span>
                         </a>
                    </li>

                    @unless($isVendor)
                    @can('create', Transaction::class)
                         <li class="nav-item">
                              <a class="nav-link @if(request()->routeIs('invoice-verification.transactions.create')) active @endif" href="{{ route('invoice-verification.transactions.create') }}">
                                   <span class="nav-icon"><iconify-icon icon="solar:add-square-outline"></iconify-icon></span>
                                   <span class="nav-text">Buat Transaksi</span>
                              </a>
                         </li>
                    @endcan
                    @endunless

                    @if($canViewApprovals)
                         <li class="nav-item">
                              <a class="nav-link @if(request()->routeIs('invoice-verification.approvals.*')) active @endif" href="{{ route('invoice-verification.approvals.index') }}">
                                   <span class="nav-icon"><iconify-icon icon="solar:check-read-outline"></iconify-icon></span>
                                   <span class="nav-text">Inbox Approval</span>
                              </a>
                         </li>
                    @endif

                    @if($isVendor)
                         <li class="nav-item">
                              <a class="nav-link @if($isRevisionList) active @endif" href="{{ route('invoice-verification.transactions.index', ['status' => TransactionStatus::NOT_APPROVED->value]) }}">
                                   <span class="nav-icon"><iconify-icon icon="solar:refresh-circle-outline"></iconify-icon></span>
                                   <span class="nav-text">Revisi</span>
                              </a>
                         </li>
                    @endif

                    @if($isAccounting)
                         <li class="nav-item">
                              <a class="nav-link @if($isAccountingReview) active @endif" href="{{ route('invoice-verification.transactions.index', ['status' => TransactionStatus::IN_REVIEW->value]) }}">
                                   <span class="nav-icon"><iconify-icon icon="solar:checklist-minimalistic-outline"></iconify-icon></span>
                                   <span class="nav-text">Verifikasi Accounting</span>
                              </a>
                         </li>
                    @endif

                    @unless($isVendor)
                         <li class="menu-title"><span>Pengelolaan</span></li>
                    @endunless

                    @if($canManageFinalization)
                         <li class="nav-item">
                              <a class="nav-link @if(request()->routeIs('invoice-verification.numbering-registers.*')) active @endif" href="{{ route('invoice-verification.numbering-registers.index') }}">
                                   <span class="nav-icon"><iconify-icon icon="solar:hashtag-square-outline"></iconify-icon></span>
                                   <span class="nav-text">Numbering Register</span>
                              </a>
                         </li>

                         <li class="nav-item">
                              <a class="nav-link @if(request()->routeIs('invoice-verification.compiled-documents.*')) active @endif" href="{{ route('invoice-verification.compiled-documents.index') }}">
                                   <span class="nav-icon"><iconify-icon icon="solar:folder-with-files-outline"></iconify-icon></span>
                                   <span class="nav-text">Dokumen Kompilasi</span>
                              </a>
                         </li>

                         <li class="nav-item">
                              <a class="nav-link @if(request()->routeIs('invoice-verification.archive.*')) active @endif" href="{{ route('invoice-verification.archive.index') }}">
                                   <span class="nav-icon"><iconify-icon icon="solar:archive-outline"></iconify-icon></span>
                                   <span class="nav-text">Arsip</span>
                              </a>
                         </li>
                    @endif

                    @if($user?->hasRole(RoleCode::ADMIN_DIVISI))
                         <li class="nav-item">
                              <a class="nav-link @if(request()->routeIs('invoice-verification.master-data.*')) active @endif" href="{{ route('invoice-verification.master-data.index') }}">
                                   <span class="nav-icon"><iconify-icon icon="solar:database-outline"></iconify-icon></span>
                                   <span class="nav-text">Master Data</span>
                              </a>
                         </li>
                    @endif

                    @unless($isVendor)
                         <li class="nav-item">
                              <a class="nav-link @if(request()->routeIs('invoice-verification.audit-logs.*')) active @endif" href="{{ route('invoice-verification.audit-logs.index') }}">
                                   <span class="nav-icon"><iconify-icon icon="solar:history-2-outline"></iconify-icon></span>
                                   <span class="nav-text">Log Audit</span>
                              </a>
                         </li>
                    @endunless
               </ul>
          </nav>
     </div>
</aside>
