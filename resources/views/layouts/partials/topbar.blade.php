<header class="app-topbar">
     <div class="container-fluid">
          <div class="navbar-header">
               <div class="d-flex align-items-center gap-2">
                    <div class="topbar-item">
                         <button type="button" class="button-toggle-menu topbar-button">
                              <iconify-icon icon="solar:hamburger-menu-outline" class="fs-24 align-middle"></iconify-icon>
                         </button>
                    </div>

                    <form class="app-search d-none d-md-block me-auto" action="{{ route('invoice-verification.transactions.index') }}" method="GET">
                         <div class="position-relative">
                              <input type="search" class="form-control" name="search" placeholder="cari nomor registrasi, vendor, judul"
                                   autocomplete="off" value="{{ request('search') }}">
                              <iconify-icon icon="solar:magnifer-outline" class="search-widget-icon"></iconify-icon>
                         </div>
                    </form>
               </div>

               <div class="d-flex align-items-center gap-2">
                    <div class="topbar-item">
                         <button type="button" class="topbar-button" id="light-dark-mode">
                              <iconify-icon icon="solar:moon-outline" class="fs-22 align-middle light-mode"></iconify-icon>
                              <iconify-icon icon="solar:sun-2-outline" class="fs-22 align-middle dark-mode"></iconify-icon>
                         </button>
                    </div>

                    <div class="dropdown topbar-item">
                         <a type="button" class="topbar-button" id="page-header-user-dropdown" data-bs-toggle="dropdown"
                              aria-haspopup="true" aria-expanded="false">
                              <span class="d-flex align-items-center gap-2">
                                   <span class="avatar-sm rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center fw-semibold">
                                        {{ str(auth()->user()?->name ?? 'U')->substr(0, 1)->upper() }}
                                   </span>
                                   <span class="d-none d-lg-block text-start">
                                        <span class="d-block fw-semibold">{{ auth()->user()?->name }}</span>
                                        <small class="text-muted">{{ auth()->user()?->role_code?->label() ?? auth()->user()?->role_code }}</small>
                                   </span>
                              </span>
                         </a>
                         <div class="dropdown-menu dropdown-menu-end">
                              <h6 class="dropdown-header">Session</h6>
                              <span class="dropdown-item-text text-muted small">
                                   {{ auth()->user()?->email }}
                              </span>
                              <div class="dropdown-divider my-1"></div>
                              <form method="POST" action="{{ route('logout') }}">
                                   @csrf
                                   <button class="dropdown-item text-danger" type="submit">
                                        <iconify-icon icon="solar:logout-3-outline" class="align-middle me-2 fs-18"></iconify-icon>
                                        <span class="align-middle">Logout</span>
                                   </button>
                              </form>
                         </div>
                    </div>
               </div>
          </div>
     </div>
</header>
