@extends('layouts.vertical', ['subtitle' => 'Reference Tables'])

@section('content')
@php
    $summaryCards = [
        ['label' => 'Total Vendors', 'value' => $vendorTotal, 'icon' => 'solar:buildings-2-outline', 'tone' => 'primary'],
        ['label' => 'LDAP Whitelist', 'value' => $internalUserTotal, 'icon' => 'solar:users-group-rounded-outline', 'tone' => 'success'],
        ['label' => 'Org Units', 'value' => $activeDivisionTotal.' / '.$activeDepartmentTotal, 'icon' => 'solar:hierarchy-2-outline', 'tone' => 'warning'],
        ['label' => 'Active Templates', 'value' => $activeTemplateTotal, 'icon' => 'solar:document-text-outline', 'tone' => 'secondary'],
    ];
    $tabRoute = fn (string $tab) => route('invoice-verification.master-data.index', ['tab' => $tab]);
@endphp

<style>
    .reference-page {
        --ref-border: rgba(33, 37, 41, .08);
        --ref-shadow: 0 12px 30px rgba(27, 36, 54, .07);
        color: #1f2937;
        padding-bottom: 32px;
    }

    .reference-page .page-kicker {
        font-size: .76rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .reference-page .summary-card,
    .reference-page .reference-card {
        border: 1px solid var(--ref-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: var(--ref-shadow);
    }

    .reference-page .summary-card .summary-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }

    .reference-page .reference-tabs {
        border-bottom: 1px solid var(--ref-border);
        gap: 6px;
    }

    .reference-page .reference-tabs .nav-link {
        border: 0;
        color: #667085;
        font-weight: 600;
        padding: 12px 14px;
        border-radius: 10px 10px 0 0;
    }

    .reference-page .reference-tabs .nav-link.active {
        color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), .08);
        box-shadow: inset 0 -2px 0 var(--bs-primary);
    }

    .reference-page .reference-toolbar {
        display: grid;
        grid-template-columns: minmax(260px, 1fr) 170px 180px auto;
        gap: 12px;
        align-items: center;
    }

    .reference-page .search-control .input-group-text {
        background: #fff;
        border-right: 0;
    }

    .reference-page .search-control .form-control {
        border-left: 0;
    }

    .reference-page .reference-table {
        --bs-table-bg: transparent;
    }

    .reference-page .reference-table thead th {
        border-top: 0;
        border-bottom: 1px solid var(--ref-border);
        background: #f8fafc;
        color: #667085;
        font-size: .74rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .reference-page .reference-table tbody td {
        border-color: rgba(33, 37, 41, .055);
        padding-top: .62rem;
        padding-bottom: .62rem;
        line-height: 1.25;
        vertical-align: middle;
    }

    .reference-page .table-action {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .reference-page .status-badge {
        border-radius: 999px;
        padding: 5px 10px;
        font-weight: 700;
        font-size: .72rem;
    }

    .reference-master-drawer .offcanvas-header {
        padding: 24px 28px 20px;
    }

    .reference-master-drawer .offcanvas-body {
        padding: 24px 28px 32px;
    }

    .reference-master-drawer .form-label {
        margin-bottom: 10px;
        color: #53677d;
        font-weight: 700;
    }

    .reference-master-drawer .form-control,
    .reference-master-drawer .form-select {
        min-height: 46px;
        border-radius: 10px;
    }

    .reference-page .drawer-footer,
    .reference-master-drawer .drawer-footer {
        position: sticky;
        bottom: 0;
        z-index: 2;
        background: #fff;
        border-top: 1px solid var(--ref-border);
        padding: 20px 28px calc(20px + env(safe-area-inset-bottom));
        gap: 14px !important;
        box-shadow: 0 -10px 24px rgba(27, 36, 54, .05);
    }

    .reference-master-drawer .drawer-footer .btn {
        min-height: 44px;
        padding-left: 22px;
        padding-right: 22px;
        border-radius: 10px;
    }

    .reference-master-drawer .drawer-footer .btn-primary {
        min-width: 170px;
    }

    @media (max-width: 575.98px) {
        .reference-master-drawer .drawer-footer {
            justify-content: stretch !important;
            flex-direction: column-reverse;
        }

        .reference-master-drawer .drawer-footer .btn {
            width: 100%;
        }
    }

    .reference-page .empty-state {
        min-height: 180px;
        display: grid;
        place-items: center;
        color: #667085;
    }

    .reference-loading {
        display: none;
        padding: 14px 20px 0;
    }

    .reference-loading.is-visible {
        display: block;
    }

    .skeleton-line {
        height: 12px;
        border-radius: 999px;
        background: linear-gradient(90deg, #eef2f7, #f8fafc, #eef2f7);
        background-size: 200% 100%;
        animation: skeleton-pulse 1.1s ease-in-out infinite;
    }

    @keyframes skeleton-pulse {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    @media (max-width: 991.98px) {
        .reference-page .reference-toolbar {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="reference-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <div class="page-kicker text-primary mb-1">Master Data</div>
            <h3 class="mb-1 fw-bold">Reference Tables</h3>
            <p class="text-muted mb-0">Kelola master data vendor, organisasi, LDAP whitelist, memo, agreement, dan template dokumen.</p>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('invoice-verification.dashboard') }}" class="text-muted">Invoice Verification</a></li>
                    <li class="breadcrumb-item active text-muted" aria-current="page">Reference Tables</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-primary d-inline-flex align-items-center gap-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#eprocImportDrawer">
                <iconify-icon icon="solar:upload-outline" class="fs-18"></iconify-icon>
                Import E-Proc
            </button>
            <form method="POST" action="{{ route('invoice-verification.master-data.ldap-sync', ['tab' => 'ldap']) }}">
                @csrf
                <button class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                    <iconify-icon icon="solar:refresh-outline" class="fs-18"></iconify-icon>
                    Sync LDAP
                </button>
            </form>
            <button class="btn btn-primary d-inline-flex align-items-center gap-2" type="button" data-master-add-button data-bs-toggle="offcanvas" data-bs-target="#vendorDrawer">
                <iconify-icon icon="solar:add-circle-outline" class="fs-18"></iconify-icon>
                <span data-master-add-label>Tambah Vendor</span>
            </button>
        </div>
    </div>

    @include('invoice-verification.partials.flash')

    <div class="row g-3 mb-4">
        @foreach ($summaryCards as $card)
            <div class="col-md-6 col-xl-3">
                <div class="summary-card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="text-muted small">{{ $card['label'] }}</div>
                            <div class="h3 mb-0 fw-bold">{{ $card['value'] }}</div>
                        </div>
                        <span class="summary-icon bg-{{ $card['tone'] }}-subtle text-{{ $card['tone'] }}">
                            <iconify-icon icon="{{ $card['icon'] }}" class="fs-22"></iconify-icon>
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card reference-card mb-0">
        <div class="card-header bg-white border-0 px-4 pt-4 pb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Reference Tables</h5>
                    <p class="text-muted mb-0">Cari, filter, dan kelola data referensi operasional.</p>
                </div>
                <span class="badge bg-primary-subtle text-primary px-3 py-2">Data Management</span>
            </div>
            <ul class="nav nav-tabs reference-tabs" role="tablist">
                <li class="nav-item"><a href="{{ $tabRoute('vendors') }}" class="nav-link {{ $activeTab === 'vendors' ? 'active' : '' }}" data-reference-tab="vendors" role="tab">Vendors</a></li>
                <li class="nav-item"><a href="{{ $tabRoute('organization') }}" class="nav-link {{ $activeTab === 'organization' ? 'active' : '' }}" data-reference-tab="organization" role="tab">Organization</a></li>
                <li class="nav-item"><a href="{{ $tabRoute('ldap') }}" class="nav-link {{ $activeTab === 'ldap' ? 'active' : '' }}" data-reference-tab="ldap" role="tab">LDAP Whitelist</a></li>
                <li class="nav-item"><a href="{{ $tabRoute('memo') }}" class="nav-link {{ $activeTab === 'memo' ? 'active' : '' }}" data-reference-tab="memo" role="tab">Memo</a></li>
                <li class="nav-item"><a href="{{ $tabRoute('agreements') }}" class="nav-link {{ $activeTab === 'agreements' ? 'active' : '' }}" data-reference-tab="agreements" role="tab">Agreements</a></li>
                <li class="nav-item"><a href="{{ $tabRoute('templates') }}" class="nav-link {{ $activeTab === 'templates' ? 'active' : '' }}" data-reference-tab="templates" role="tab">Templates</a></li>
            </ul>
        </div>

        <div class="card-body p-4">
            <form class="reference-toolbar mb-3" method="GET" action="{{ route('invoice-verification.master-data.index') }}" data-reference-filter-form>
                <input type="hidden" name="tab" value="{{ $activeTab }}" data-reference-tab-input>
                <div class="input-group search-control">
                    <span class="input-group-text"><iconify-icon icon="solar:magnifer-outline" class="fs-18"></iconify-icon></span>
                    <input type="search" class="form-control" name="q" value="{{ $search }}" placeholder="Search current table..." data-reference-search>
                </div>
                <select class="form-select" name="status" data-reference-status>
                    <option value="">All Status</option>
                    <option value="active" @selected($statusFilter === 'active')>Active</option>
                    <option value="inactive" @selected($statusFilter === 'inactive')>Inactive</option>
                    <option value="draft" @selected($statusFilter === 'draft')>Draft</option>
                    <option value="pending" @selected($statusFilter === 'pending')>Pending</option>
                </select>
                <select class="form-select" name="type" data-reference-type>
                    <option value="">All Type</option>
                    <option value="vendor" @selected($typeFilter === 'vendor')>Vendor</option>
                    <option value="division" @selected($typeFilter === 'division')>Division</option>
                    <option value="department" @selected($typeFilter === 'department')>Department</option>
                    <option value="ldap" @selected($typeFilter === 'ldap')>LDAP</option>
                    <option value="memo" @selected($typeFilter === 'memo')>Memo</option>
                    <option value="agreement" @selected($typeFilter === 'agreement')>Agreement</option>
                    <option value="template" @selected($typeFilter === 'template')>Template</option>
                </select>
                <button class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2" type="button" data-master-add-button data-bs-toggle="offcanvas" data-bs-target="#vendorDrawer">
                    <iconify-icon icon="solar:add-circle-outline" class="fs-18"></iconify-icon>
                    <span data-master-add-label>Tambah Vendor</span>
                </button>
            </form>

            <div class="reference-loading" data-reference-loading>
                <div class="skeleton-line mb-2" style="width: 94%;"></div>
                <div class="skeleton-line mb-2" style="width: 78%;"></div>
                <div class="skeleton-line" style="width: 86%;"></div>
            </div>

            <div class="tab-content">
                <div class="tab-pane {{ $activeTab === 'vendors' ? 'show active' : '' }}" id="vendors" data-reference-panel="vendors" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table reference-table table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Vendor Name</th>
                                    <th>NPWP</th>
                                    <th>Contact Name</th>
                                    <th>Contact Email</th>
                                    <th>Bank Name</th>
                                    <th>Nomor Rekening</th>
                                    <th>Status</th>
                                    <th>Updated At</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vendors as $vendor)
                                    <tr data-reference-row data-status="active" data-type="vendor" data-search="{{ str($vendor->name.' '.$vendor->npwp.' '.$vendor->contact_name.' '.$vendor->contact_email.' '.$vendor->defaultBank?->name)->lower() }}">
                                        <td class="fw-semibold">{{ $vendor->name }}</td>
                                        <td>{{ $vendor->npwp ?? '-' }}</td>
                                        <td>{{ $vendor->contact_name ?? '-' }}</td>
                                        <td>{{ $vendor->contact_email ?? '-' }}</td>
                                        <td>{{ $vendor->defaultBank?->name ?? '-' }}</td>
                                        <td>{{ $vendor->default_account_number ?? '-' }}</td>
                                        <td><span class="status-badge bg-success-subtle text-success">Active</span></td>
                                        <td>{{ $vendor->updated_at?->format('d M Y') ?? '-' }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light table-action" type="button" title="View" data-bs-toggle="offcanvas" data-bs-target="#vendorDetailDrawer"
                                                data-detail-title="{{ $vendor->name }}"
                                                data-detail-body="NPWP: {{ $vendor->npwp ?? '-' }}|Contact: {{ $vendor->contact_name ?? '-' }}|Email: {{ $vendor->contact_email ?? '-' }}|Bank: {{ $vendor->defaultBank?->name ?? '-' }}|Account: {{ $vendor->default_account_number ?? '-' }}">
                                                <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                            </button>
                                            <button class="btn btn-sm btn-light table-action" type="button" title="Edit" data-bs-toggle="offcanvas" data-bs-target="#vendorDrawer"
                                                data-vendor-edit
                                                data-action="{{ route('invoice-verification.master-data.vendors.update', [$vendor, 'tab' => 'vendors']) }}"
                                                data-name="{{ $vendor->name }}"
                                                data-npwp="{{ $vendor->npwp }}"
                                                data-contact-name="{{ $vendor->contact_name }}"
                                                data-contact-email="{{ $vendor->contact_email }}"
                                                data-contact-phone="{{ $vendor->contact_phone }}"
                                                data-bank-name="{{ $vendor->defaultBank?->name }}"
                                                data-account-number="{{ $vendor->default_account_number }}">
                                                <iconify-icon icon="solar:pen-outline" class="fs-18"></iconify-icon>
                                            </button>
                                            <button class="btn btn-sm btn-light table-action" type="button" title="Disable" data-confirm-message="Status vendor saat ini hanya informatif. Gunakan pengaturan vendor lanjutan untuk menonaktifkan akun vendor.">
                                                <iconify-icon icon="solar:pause-circle-outline" class="fs-18"></iconify-icon>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr data-empty-row>
                                        <td colspan="9">
                                            <div class="empty-state">
                                                <div class="text-center">
                                                    <iconify-icon icon="solar:box-minimalistic-outline" class="fs-34 d-block mb-2"></iconify-icon>
                                                    <div class="fw-semibold">Belum ada data</div>
                                                    <div>Klik Tambah Vendor untuk menambahkan data baru.</div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                                <tr class="d-none" data-no-results-row><td colspan="9" class="text-center text-muted py-4">Tidak ada data yang cocok dengan pencarian.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pt-3">
                        {{ $vendors->links() }}
                    </div>
                </div>

                <div class="tab-pane {{ $activeTab === 'organization' ? 'show active' : '' }}" id="organization" data-reference-panel="organization" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                        <div>
                            <h6 class="mb-1">Master Divisi & Department</h6>
                            <p class="text-muted mb-0 small">Data organisasi ini juga tersedia sebagai open API read-only untuk aplikasi internal.</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#divisionDrawer" data-division-create>
                                <iconify-icon icon="solar:add-circle-outline" class="me-1"></iconify-icon>Tambah Divisi
                            </button>
                            <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#departmentDrawer" data-department-create>
                                <iconify-icon icon="solar:add-circle-outline" class="me-1"></iconify-icon>Tambah Department
                            </button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-xl-5">
                            <div class="border rounded-3 h-100">
                                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">Divisi</div>
                                        <div class="text-muted small">{{ $divisions->total() }} data terdaftar</div>
                                    </div>
                                    <span class="status-badge bg-primary-subtle text-primary">{{ $activeDivisionTotal }} Active</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table reference-table table-nowrap mb-0">
                                        <thead>
                                            <tr>
                                                <th>Nama Divisi</th>
                                                <th>LDAP Code</th>
                                                <th>Plafon Kas Kecil</th>
                                                <th>Dept</th>
                                                <th>Status</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($divisions as $division)
                                                <tr data-reference-row data-status="{{ $division->is_active ? 'active' : 'inactive' }}" data-type="division" data-search="{{ str($division->name.' '.$division->ldap_code)->lower() }}">
                                                    <td class="fw-semibold">{{ $division->name }}</td>
                                                    <td>{{ $division->ldap_code ?? '-' }}</td>
                                                    <td>{{ $division->petty_cash_ceiling !== null ? number_format((float) $division->petty_cash_ceiling, 2, ',', '.') : '-' }}</td>
                                                    <td><span class="badge bg-light text-dark border">{{ $division->departments_count }}</span></td>
                                                    <td>
                                                        <span class="status-badge {{ $division->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                            {{ $division->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <button
                                                            class="btn btn-sm btn-light table-action"
                                                            type="button"
                                                            title="Edit"
                                                            data-bs-toggle="offcanvas"
                                                            data-bs-target="#divisionDrawer"
                                                            data-division-edit
                                                            data-action="{{ route('invoice-verification.master-data.divisions.update', [$division, 'tab' => 'organization']) }}"
                                                            data-name="{{ $division->name }}"
                                                            data-ldap-code="{{ $division->ldap_code }}"
                                                            data-petty-cash-ceiling="{{ $division->petty_cash_ceiling }}"
                                                            data-is-active="{{ $division->is_active ? '1' : '0' }}"
                                                        >
                                                            <iconify-icon icon="solar:pen-outline" class="fs-18"></iconify-icon>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr data-empty-row><td colspan="6"><div class="empty-state"><div class="text-center"><div class="fw-semibold">Belum ada divisi</div><div>Klik Tambah Divisi untuk menambahkan data.</div></div></div></td></tr>
                                            @endforelse
                                            <tr class="d-none" data-no-results-row><td colspan="6" class="text-center text-muted py-4">Tidak ada divisi yang cocok dengan pencarian.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="p-3 border-top">
                                    {{ $divisions->links() }}
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-7">
                            <div class="border rounded-3 h-100">
                                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">Department</div>
                                        <div class="text-muted small">{{ $departments->total() }} data terdaftar</div>
                                    </div>
                                    <span class="status-badge bg-primary-subtle text-primary">{{ $activeDepartmentTotal }} Active</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table reference-table table-nowrap mb-0">
                                        <thead>
                                            <tr>
                                                <th>Department</th>
                                                <th>Divisi</th>
                                                <th>LDAP Code</th>
                                                <th>Status</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($departments as $department)
                                                <tr data-reference-row data-status="{{ $department->is_active && $department->division?->is_active ? 'active' : 'inactive' }}" data-type="department" data-search="{{ str($department->name.' '.$department->ldap_code.' '.$department->division?->name.' '.$department->division?->ldap_code)->lower() }}">
                                                    <td class="fw-semibold">{{ $department->name }}</td>
                                                    <td>{{ $department->division?->name ?? '-' }}</td>
                                                    <td>{{ $department->ldap_code ?? '-' }}</td>
                                                    <td>
                                                        <span class="status-badge {{ $department->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                            {{ $department->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <button
                                                            class="btn btn-sm btn-light table-action"
                                                            type="button"
                                                            title="Edit"
                                                            data-bs-toggle="offcanvas"
                                                            data-bs-target="#departmentDrawer"
                                                            data-department-edit
                                                            data-action="{{ route('invoice-verification.master-data.departments.update', [$department, 'tab' => 'organization']) }}"
                                                            data-name="{{ $department->name }}"
                                                            data-division-id="{{ $department->division_id }}"
                                                            data-ldap-code="{{ $department->ldap_code }}"
                                                            data-is-active="{{ $department->is_active ? '1' : '0' }}"
                                                        >
                                                            <iconify-icon icon="solar:pen-outline" class="fs-18"></iconify-icon>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr data-empty-row><td colspan="5"><div class="empty-state"><div class="text-center"><div class="fw-semibold">Belum ada department</div><div>Klik Tambah Department untuk menambahkan data.</div></div></div></td></tr>
                                            @endforelse
                                            <tr class="d-none" data-no-results-row><td colspan="5" class="text-center text-muted py-4">Tidak ada department yang cocok dengan pencarian.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="p-3 border-top">
                                    {{ $departments->links() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-3 mb-0">
                        <div class="fw-semibold mb-1">Open API</div>
                        <div class="small text-muted">
                            Gunakan <code>/api/invoice-verification/organization/tree</code>,
                            <code>/api/invoice-verification/organization/divisions</code>, atau
                            <code>/api/invoice-verification/organization/departments</code> untuk integrasi aplikasi internal.
                        </div>
                    </div>
                </div>

                <div class="tab-pane {{ $activeTab === 'ldap' ? 'show active' : '' }}" id="ldap-whitelist" data-reference-panel="ldap" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table reference-table table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Nama User</th>
                                    <th>Email LDAP</th>
                                    <th>Role</th>
                                    <th>Divisi</th>
                                    <th>Departemen</th>
                                    <th>LDAP UID</th>
                                    <th>NIP</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($internalUsers as $internalUser)
                                    <tr data-reference-row data-status="{{ $internalUser->is_active ? 'active' : 'inactive' }}" data-type="ldap" data-search="{{ str($internalUser->name.' '.$internalUser->email.' '.$internalUser->role_code?->label().' '.$internalUser->division?->name.' '.$internalUser->department?->name.' '.$internalUser->ldap_uid.' '.$internalUser->employee_number)->lower() }}">
                                        <td class="fw-semibold">{{ $internalUser->name }}</td>
                                        <td>{{ $internalUser->email }}</td>
                                        <td>{{ $internalUser->role_code?->label() ?? '-' }}</td>
                                        <td>{{ $internalUser->division?->name ?? '-' }}</td>
                                        <td>{{ $internalUser->department?->name ?? '-' }}</td>
                                        <td>{{ $internalUser->ldap_uid ?? '-' }}</td>
                                        <td>{{ $internalUser->employee_number ?? '-' }}</td>
                                        <td>
                                            <span class="status-badge {{ $internalUser->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                {{ $internalUser->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <button
                                                class="btn btn-sm btn-light table-action"
                                                type="button"
                                                title="Edit"
                                                data-bs-toggle="offcanvas"
                                                data-bs-target="#ldapDrawer"
                                                data-ldap-edit
                                                data-action="{{ route('invoice-verification.master-data.ldap-whitelist.update', [$internalUser, 'tab' => 'ldap']) }}"
                                                data-name="{{ $internalUser->name }}"
                                                data-email="{{ $internalUser->email }}"
                                                data-role-code="{{ $internalUser->role_code?->value }}"
                                                data-division-id="{{ $internalUser->division_id }}"
                                                data-department-id="{{ $internalUser->department_id }}"
                                                data-ldap-uid="{{ $internalUser->ldap_uid }}"
                                                data-employee-number="{{ $internalUser->employee_number }}"
                                                data-is-active="{{ $internalUser->is_active ? '1' : '0' }}"
                                            >
                                                <iconify-icon icon="solar:pen-outline" class="fs-18"></iconify-icon>
                                            </button>
                                            <form method="POST" action="{{ route('invoice-verification.master-data.ldap-whitelist.update', [$internalUser, 'tab' => 'ldap']) }}" class="d-inline" data-confirm-form data-confirm-message="{{ $internalUser->is_active ? 'Nonaktifkan akses LDAP user ini?' : 'Aktifkan akses LDAP user ini?' }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $internalUser->is_active ? '0' : '1' }}">
                                                <button class="btn btn-sm btn-light table-action" type="submit" title="{{ $internalUser->is_active ? 'Disable' : 'Activate' }}">
                                                    <iconify-icon icon="{{ $internalUser->is_active ? 'solar:pause-circle-outline' : 'solar:play-circle-outline' }}" class="fs-18"></iconify-icon>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr data-empty-row>
                                        <td colspan="9">
                                            <div class="empty-state">
                                                <div class="text-center">
                                                    <iconify-icon icon="solar:user-cross-outline" class="fs-34 d-block mb-2"></iconify-icon>
                                                    <div class="fw-semibold">Belum ada data</div>
                                                    <div>Klik Tambah LDAP User untuk menambahkan whitelist.</div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                                <tr class="d-none" data-no-results-row><td colspan="9" class="text-center text-muted py-4">Tidak ada data yang cocok dengan pencarian.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pt-3">
                        {{ $internalUsers->links() }}
                    </div>
                </div>

                <div class="tab-pane {{ $activeTab === 'memo' ? 'show active' : '' }}" id="memo" data-reference-panel="memo" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table reference-table table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Name / Title</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Updated At</th>
                                    <th>Uploader</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($memoRequests as $memo)
                                    <tr data-reference-row data-status="{{ $memo->file_path ? 'active' : 'pending' }}" data-type="memo" data-search="{{ str($memo->memo_number.' '.$memo->subject.' '.$memo->creator?->name)->lower() }}">
                                        <td>
                                            <div class="fw-semibold">{{ $memo->subject }}</div>
                                            <div class="text-muted small">{{ $memo->memo_number }} - {{ $memo->memo_date?->format('d M Y') }}</div>
                                        </td>
                                        <td>Memo</td>
                                        <td><span class="status-badge {{ $memo->file_path ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $memo->file_path ? 'Active' : 'Pending' }}</span></td>
                                        <td>{{ $memo->updated_at?->format('d M Y') ?? '-' }}</td>
                                        <td>{{ $memo->creator?->name ?? '-' }}</td>
                                        <td class="text-end">
                                            @if($memo->file_path)
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-light table-action"
                                                    title="Preview"
                                                    data-file-preview-url="{{ route('invoice-verification.master-data.memo-requests.preview', $memo) }}"
                                                    data-file-preview-title="Memo - {{ $memo->memo_number }}"
                                                >
                                                    <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr data-empty-row><td colspan="6"><div class="empty-state"><div class="text-center"><div class="fw-semibold">Belum ada data</div><div>Klik Tambah Memo untuk menambahkan data baru.</div></div></div></td></tr>
                                @endforelse
                                <tr class="d-none" data-no-results-row><td colspan="6" class="text-center text-muted py-4">Tidak ada data yang cocok dengan pencarian.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pt-3">
                        {{ $memoRequests->links() }}
                    </div>
                </div>

                <div class="tab-pane {{ $activeTab === 'agreements' ? 'show active' : '' }}" id="agreements" data-reference-panel="agreements" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table reference-table table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Name / Title</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Updated At</th>
                                    <th>Vendor</th>
                                    <th>Value</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($agreementReferences as $agreement)
                                    <tr data-reference-row data-status="{{ $agreement->file_path ? 'active' : 'pending' }}" data-type="agreement" data-search="{{ str($agreement->contract_number.' '.$agreement->vendor?->name.' '.$agreement->division?->name.' '.$agreement->department?->name)->lower() }}">
                                        <td>
                                            <div class="fw-semibold">{{ $agreement->contract_number }}</div>
                                            <div class="text-muted small">{{ $agreement->division?->name ?? '-' }} / {{ $agreement->department?->name ?? '-' }}</div>
                                        </td>
                                        <td>Agreement</td>
                                        <td><span class="status-badge {{ $agreement->file_path ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $agreement->file_path ? 'Active' : 'Pending' }}</span></td>
                                        <td>{{ $agreement->updated_at?->format('d M Y') ?? '-' }}</td>
                                        <td>{{ $agreement->vendor?->name ?? '-' }}</td>
                                        <td>{{ number_format((float) $agreement->contract_value, 2, ',', '.') }}</td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light table-action"
                                                title="Detail"
                                                data-bs-toggle="offcanvas"
                                                data-bs-target="#vendorDetailDrawer"
                                                data-detail-title="Agreement - {{ $agreement->contract_number }}"
                                                data-detail-body="Vendor: {{ $agreement->vendor?->name ?? '-' }}|Divisi: {{ $agreement->division?->name ?? '-' }}|Department: {{ $agreement->department?->name ?? '-' }}|Nilai: {{ number_format((float) $agreement->contract_value, 2, ',', '.') }}|Tanggal Efektif: {{ $agreement->effective_date?->format('d M Y') ?? '-' }}|File: {{ $agreement->file_name ?? 'Belum ada file' }}"
                                            >
                                                <iconify-icon icon="solar:document-text-outline" class="fs-18"></iconify-icon>
                                            </button>
                                            @if($agreement->file_path)
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-light table-action"
                                                    title="Preview"
                                                    data-file-preview-url="{{ route('invoice-verification.master-data.agreement-references.preview', $agreement) }}"
                                                    data-file-preview-title="Kontrak - {{ $agreement->contract_number }}"
                                                >
                                                    <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-light table-action"
                                                    title="Upload File"
                                                    data-bs-toggle="offcanvas"
                                                    data-bs-target="#agreementFileDrawer"
                                                    data-agreement-file-upload
                                                    data-action="{{ route('invoice-verification.master-data.agreement-references.file.update', $agreement) }}"
                                                    data-contract-number="{{ $agreement->contract_number }}"
                                                >
                                                    <iconify-icon icon="solar:upload-outline" class="fs-18"></iconify-icon>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr data-empty-row><td colspan="7"><div class="empty-state"><div class="text-center"><div class="fw-semibold">Belum ada data</div><div>Klik Tambah Agreement untuk menambahkan data baru.</div></div></div></td></tr>
                                @endforelse
                                <tr class="d-none" data-no-results-row><td colspan="7" class="text-center text-muted py-4">Tidak ada data yang cocok dengan pencarian.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pt-3">
                        {{ $agreementReferences->links() }}
                    </div>
                </div>

                <div class="tab-pane {{ $activeTab === 'templates' ? 'show active' : '' }}" id="templates" data-reference-panel="templates" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table reference-table table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Name / Title</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Updated At</th>
                                    <th>Document Code</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($templateReferences as $template)
                                    <tr data-reference-row data-status="{{ $template->is_active ? 'active' : 'inactive' }}" data-type="template" data-search="{{ str($template->code.' '.$template->name.' '.$template->template_type?->value.' '.$template->document_code)->lower() }}">
                                        <td>
                                            <div class="fw-semibold">{{ $template->name }}</div>
                                            <div class="text-muted small">{{ $template->code }}</div>
                                        </td>
                                        <td>{{ $template->template_type?->value ?? '-' }}</td>
                                        <td>
                                            <span class="status-badge {{ $template->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                {{ $template->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ $template->updated_at?->format('d M Y') ?? '-' }}</td>
                                        <td>{{ $template->document_code ?? '-' }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light table-action" type="button" title="View" data-bs-toggle="offcanvas" data-bs-target="#templateDrawer">
                                                <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr data-empty-row><td colspan="6"><div class="empty-state"><div class="text-center"><div class="fw-semibold">Belum ada data</div><div>Klik Tambah Template untuk menambahkan data baru.</div></div></div></td></tr>
                                @endforelse
                                <tr class="d-none" data-no-results-row><td colspan="6" class="text-center text-muted py-4">Tidak ada data yang cocok dengan pencarian.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pt-3">
                        {{ $templateReferences->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end reference-master-drawer" tabindex="-1" id="divisionDrawer" aria-labelledby="divisionDrawerLabel" style="width: min(460px, 100vw);">
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title" id="divisionDrawerLabel">Tambah Divisi</h5>
            <p class="text-muted mb-0 small">Kelola master divisi untuk workflow dan API organisasi.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <form method="POST" action="{{ route('invoice-verification.master-data.divisions.store', ['tab' => 'organization']) }}" class="d-flex flex-column h-100" id="divisionForm" data-store-action="{{ route('invoice-verification.master-data.divisions.store', ['tab' => 'organization']) }}">
        @csrf
        <input type="hidden" name="_method" value="PUT" data-division-method disabled>
        <div class="offcanvas-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Nama Divisi</label>
                    <input class="form-control" name="name" value="{{ old('name') }}" data-division-field="name" required>
                </div>
                <div class="col-12">
                    <label class="form-label">LDAP Code</label>
                    <input class="form-control" name="ldap_code" value="{{ old('ldap_code') }}" data-division-field="ldapCode" placeholder="Opsional">
                </div>
                <div class="col-12">
                    <label class="form-label">Plafon Kas Kecil</label>
                    <input type="text" inputmode="numeric" class="form-control" name="petty_cash_ceiling" value="{{ old('petty_cash_ceiling') }}" data-division-field="pettyCashCeiling" data-rupiah-input placeholder="Opsional">
                </div>
                <div class="col-12">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="is_active" data-division-field="isActive">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="drawer-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
            <button class="btn btn-primary" data-division-submit>Simpan Divisi</button>
        </div>
    </form>
</div>

<div class="offcanvas offcanvas-end reference-master-drawer" tabindex="-1" id="departmentDrawer" aria-labelledby="departmentDrawerLabel" style="width: min(500px, 100vw);">
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title" id="departmentDrawerLabel">Tambah Department</h5>
            <p class="text-muted mb-0 small">Kelola master department dan relasi ke divisi.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <form method="POST" action="{{ route('invoice-verification.master-data.departments.store', ['tab' => 'organization']) }}" class="d-flex flex-column h-100" id="departmentForm" data-store-action="{{ route('invoice-verification.master-data.departments.store', ['tab' => 'organization']) }}">
        @csrf
        <input type="hidden" name="_method" value="PUT" data-department-method disabled>
        <div class="offcanvas-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Divisi</label>
                    <select class="form-select" name="division_id" data-department-field="divisionId" required>
                        <option value="">Pilih divisi</option>
                        @foreach ($divisionOptions as $division)
                            <option value="{{ $division->id }}">{{ $division->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Nama Department</label>
                    <input class="form-control" name="name" value="{{ old('name') }}" data-department-field="name" required>
                </div>
                <div class="col-12">
                    <label class="form-label">LDAP Code</label>
                    <input class="form-control" name="ldap_code" value="{{ old('ldap_code') }}" data-department-field="ldapCode" placeholder="Opsional">
                </div>
                <div class="col-12">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="is_active" data-department-field="isActive">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="drawer-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
            <button class="btn btn-primary" data-department-submit>Simpan Department</button>
        </div>
    </form>
</div>

<div class="offcanvas offcanvas-end reference-master-drawer" tabindex="-1" id="vendorDrawer" aria-labelledby="vendorDrawerLabel" style="width: min(520px, 100vw);">
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title" id="vendorDrawerLabel">Tambah Vendor</h5>
            <p class="text-muted mb-0 small">Buat master vendor dan akun login vendor bila email diisi.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <form method="POST" action="{{ route('invoice-verification.master-data.vendors.store', ['tab' => 'vendors']) }}" class="d-flex flex-column h-100" id="vendorForm" data-store-action="{{ route('invoice-verification.master-data.vendors.store', ['tab' => 'vendors']) }}">
        @csrf
        <input type="hidden" name="_method" value="PUT" data-vendor-method disabled>
        <div class="offcanvas-body">
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Vendor Name</label><input class="form-control" name="name" value="{{ old('name') }}" data-vendor-field="name"></div>
                <div class="col-12"><label class="form-label">NPWP</label><input class="form-control" name="npwp" value="{{ old('npwp') }}" data-vendor-field="npwp"></div>
                <div class="col-12"><label class="form-label">Contact Name</label><input class="form-control" name="contact_name" value="{{ old('contact_name') }}" data-vendor-field="contactName"></div>
                <div class="col-12"><label class="form-label">Contact Email</label><input class="form-control @error('contact_email') is-invalid @enderror" type="email" name="contact_email" value="{{ old('contact_email') }}" data-vendor-field="contactEmail">@error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label">Contact Phone</label><input class="form-control" name="contact_phone" value="{{ old('contact_phone') }}" data-vendor-field="contactPhone"></div>
                <div class="col-12"><label class="form-label">Temporary Password Vendor</label><input class="form-control @error('vendor_password') is-invalid @enderror" type="password" name="vendor_password" placeholder="Minimal 8 karakter jika email diisi">@error('vendor_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label">Bank Name</label><input class="form-control" name="bank_name" value="{{ old('bank_name') }}" data-vendor-field="bankName"></div>
                <div class="col-12"><label class="form-label">Nomor Rekening</label><input class="form-control @error('default_account_number') is-invalid @enderror" name="default_account_number" value="{{ old('default_account_number') }}" inputmode="numeric" pattern="[0-9]{6,30}" maxlength="30" autocomplete="off" oninput="this.value = this.value.replace(/[^0-9]/g, '')" data-vendor-field="accountNumber">@error('default_account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" checked disabled id="vendor-active"><label class="form-check-label" for="vendor-active">Active</label></div></div>
            </div>
        </div>
        <div class="drawer-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="btn btn-primary" data-vendor-submit>Simpan Vendor</button>
        </div>
    </form>
</div>

<div class="offcanvas offcanvas-end reference-master-drawer" tabindex="-1" id="ldapDrawer" aria-labelledby="ldapDrawerLabel" style="width: min(520px, 100vw);">
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title" id="ldapDrawerLabel">Tambah LDAP User</h5>
            <p class="text-muted mb-0 small">Whitelist email internal dan role untuk login LDAP.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <form method="POST" action="{{ route('invoice-verification.master-data.ldap-whitelist.store', ['tab' => 'ldap']) }}" class="d-flex flex-column h-100" id="ldapForm" data-store-action="{{ route('invoice-verification.master-data.ldap-whitelist.store', ['tab' => 'ldap']) }}">
        @csrf
        <input type="hidden" name="_method" value="PATCH" data-ldap-method disabled>
        <div class="offcanvas-body">
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Nama User</label><input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" data-ldap-field="name">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label">Email LDAP</label><input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" data-ldap-field="email">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label">Role</label><select class="form-select @error('role_code') is-invalid @enderror" name="role_code" data-ldap-field="roleCode">@foreach ($roleOptions as $role)<option value="{{ $role->value }}" @selected(old('role_code') === $role->value)>{{ $role->label() }}</option>@endforeach</select>@error('role_code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label">Divisi</label><select class="form-select" name="division_id" data-ldap-field="divisionId"><option value="">Pilih divisi</option>@foreach ($divisionOptions as $division)<option value="{{ $division->id }}" @selected(old('division_id') === $division->id)>{{ $division->name }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Departemen</label><select class="form-select" name="department_id" data-ldap-field="departmentId"><option value="">Pilih departemen</option>@foreach ($departmentOptions as $department)<option value="{{ $department->id }}" @selected(old('department_id') === $department->id)>{{ $department->name }}</option>@endforeach</select></div>
                <div class="col-6"><label class="form-label">LDAP UID</label><input class="form-control" name="ldap_uid" value="{{ old('ldap_uid') }}" data-ldap-field="ldapUid"></div>
                <div class="col-6"><label class="form-label">NIP</label><input class="form-control" name="employee_number" value="{{ old('employee_number') }}" data-ldap-field="employeeNumber"></div>
                <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="ldap-user-active" @checked(old('is_active', '1')) data-ldap-field="isActive"><label class="form-check-label" for="ldap-user-active">Active</label></div></div>
            </div>
        </div>
        <div class="drawer-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="btn btn-primary" data-ldap-submit>Simpan Whitelist</button>
        </div>
    </form>
</div>

<div class="offcanvas offcanvas-end reference-master-drawer" tabindex="-1" id="memoDrawer" aria-labelledby="memoDrawerLabel" style="width: min(560px, 100vw);">
    <div class="offcanvas-header border-bottom"><div><h5 class="offcanvas-title" id="memoDrawerLabel">Tambah Memo</h5><p class="text-muted mb-0 small">Upload memo permohonan sebagai referensi transaksi.</p></div><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
    <form method="POST" action="{{ route('invoice-verification.master-data.memo-requests.store', ['tab' => 'memo']) }}" enctype="multipart/form-data" class="d-flex flex-column h-100">
        @csrf
        <div class="offcanvas-body">
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Nomor Memo</label><input class="form-control" name="memo_number" value="{{ old('memo_number') }}"></div>
                <div class="col-12"><label class="form-label">Tanggal Memo</label><input class="form-control" type="date" name="memo_date" value="{{ old('memo_date') }}"></div>
                <div class="col-12"><label class="form-label">Perihal</label><input class="form-control" name="subject" value="{{ old('subject') }}"></div>
                <div class="col-12"><label class="form-label">Divisi</label><select class="form-select" name="division_id">@foreach ($divisionOptions as $division)<option value="{{ $division->id }}" @selected(old('division_id', auth()->user()?->division_id) === $division->id)>{{ $division->name }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Departemen</label><select class="form-select" name="department_id">@foreach ($departmentOptions as $department)<option value="{{ $department->id }}" @selected(old('department_id', auth()->user()?->department_id) === $department->id)>{{ $department->name }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">File Memo</label><input class="form-control" type="file" name="memo_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"></div>
                <div class="col-12"><label class="form-label">Keterangan Tambahan</label><textarea class="form-control" rows="3" name="description">{{ old('description') }}</textarea></div>
            </div>
        </div>
        <div class="drawer-footer d-flex justify-content-end gap-2"><button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button><button class="btn btn-primary">Simpan Memo</button></div>
    </form>
</div>

<div class="offcanvas offcanvas-end reference-master-drawer" tabindex="-1" id="agreementDrawer" aria-labelledby="agreementDrawerLabel" style="width: min(560px, 100vw);">
    <div class="offcanvas-header border-bottom"><div><h5 class="offcanvas-title" id="agreementDrawerLabel">Tambah Agreement</h5><p class="text-muted mb-0 small">Upload kontrak vendor sebagai referensi tagihan.</p></div><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
    <form method="POST" action="{{ route('invoice-verification.master-data.agreement-references.store', ['tab' => 'agreements']) }}" enctype="multipart/form-data" class="d-flex flex-column h-100">
        @csrf
        <div class="offcanvas-body">
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Vendor</label><select class="form-select" name="vendor_id"><option value="">Pilih vendor</option>@foreach ($vendorOptions as $vendor)<option value="{{ $vendor->id }}" @selected(old('vendor_id') === $vendor->id)>{{ $vendor->name }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Nomor Kontrak</label><input class="form-control" name="contract_number" value="{{ old('contract_number') }}"></div>
                <div class="col-12"><label class="form-label">Nilai Kontrak</label><input class="form-control" name="contract_value" value="{{ old('contract_value') }}"></div>
                <div class="col-6"><label class="form-label">Tanggal Berlaku</label><input class="form-control" type="date" name="effective_date" value="{{ old('effective_date') }}"></div>
                <div class="col-6"><label class="form-label">Tanggal Berakhir</label><input class="form-control" type="date" name="expired_at" value="{{ old('expired_at') }}"></div>
                <div class="col-12"><label class="form-label">File Agreement</label><input class="form-control" type="file" name="agreement_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"></div>
            </div>
        </div>
        <div class="drawer-footer d-flex justify-content-end gap-2"><button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button><button class="btn btn-primary">Simpan Agreement</button></div>
    </form>
</div>

<div class="offcanvas offcanvas-end reference-master-drawer" tabindex="-1" id="templateDrawer" aria-labelledby="templateDrawerLabel" style="width: min(520px, 100vw);">
    <div class="offcanvas-header border-bottom"><div><h5 class="offcanvas-title" id="templateDrawerLabel">Tambah Template</h5><p class="text-muted mb-0 small">Kelola referensi template dokumen.</p></div><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
    <form method="POST" action="{{ route('invoice-verification.master-data.template-references.store', ['tab' => 'templates']) }}" class="d-flex flex-column h-100">
        @csrf
        <div class="offcanvas-body">
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Template Code</label><input class="form-control" name="code" value="{{ old('code') }}"></div>
                <div class="col-12"><label class="form-label">Template Name</label><input class="form-control" name="name" value="{{ old('name') }}"></div>
                <div class="col-12"><label class="form-label">Template Type</label><select class="form-select" name="template_type"><option value="GENERATED_DOCUMENT">Generated Document</option><option value="FINAL_COMPILATION_ORDER">Final Compilation Order</option></select></div>
                <div class="col-12"><label class="form-label">Transaction Type</label><select class="form-select" name="transaction_type_id"><option value="">Type</option>@foreach ($transactionTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Document Code</label><input class="form-control" name="document_code" value="{{ old('document_code') }}"></div>
            </div>
        </div>
        <div class="drawer-footer d-flex justify-content-end gap-2"><button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button><button class="btn btn-primary">Simpan Template</button></div>
    </form>
</div>

<div class="offcanvas offcanvas-end reference-master-drawer" tabindex="-1" id="eprocImportDrawer" aria-labelledby="eprocImportDrawerLabel" style="width: min(560px, 100vw);">
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title" id="eprocImportDrawerLabel">Import E-Proc</h5>
            <p class="text-muted mb-0 small">Upload export vendor aktif dan list purchasing.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <form method="POST" action="{{ route('invoice-verification.master-data.eproc-import') }}" enctype="multipart/form-data" class="d-flex flex-column h-100">
        @csrf
        <div class="offcanvas-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Vendor Aktif</label>
                    <input class="form-control" type="file" name="vendor_file" accept=".xlsx,.csv,.txt">
                </div>
                <div class="col-12">
                    <label class="form-label">List Purchasing</label>
                    <input class="form-control" type="file" name="purchasing_file" accept=".xlsx,.csv,.txt">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kode Divisi</label>
                    <input class="form-control" name="division_code" value="{{ old('division_code', 'EPROC') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Divisi</label>
                    <input class="form-control" name="division_name" value="{{ old('division_name', 'E-Procurement') }}">
                </div>
            </div>
        </div>
        <div class="drawer-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
            <button class="btn btn-primary d-inline-flex align-items-center gap-2">
                <iconify-icon icon="solar:upload-outline" class="fs-18"></iconify-icon>
                Import
            </button>
        </div>
    </form>
</div>

<div class="offcanvas offcanvas-end reference-master-drawer" tabindex="-1" id="agreementFileDrawer" aria-labelledby="agreementFileDrawerLabel" style="width: min(520px, 100vw);">
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title" id="agreementFileDrawerLabel">Upload File Agreement</h5>
            <p class="text-muted mb-0 small" data-agreement-file-subtitle>Lengkapi file untuk reference PO.</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <form method="POST" action="#" enctype="multipart/form-data" class="d-flex flex-column h-100" id="agreementFileForm">
        @csrf
        <div class="offcanvas-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">File Agreement</label>
                    <input class="form-control" type="file" name="agreement_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" required>
                </div>
            </div>
        </div>
        <div class="drawer-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
            <button class="btn btn-primary d-inline-flex align-items-center gap-2">
                <iconify-icon icon="solar:upload-outline" class="fs-18"></iconify-icon>
                Upload
            </button>
        </div>
    </form>
</div>

<div class="offcanvas offcanvas-end reference-master-drawer" tabindex="-1" id="vendorDetailDrawer" aria-labelledby="vendorDetailDrawerLabel" style="width: min(440px, 100vw);">
    <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title" id="vendorDetailDrawerLabel">Detail Vendor</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
    <div class="offcanvas-body" data-detail-content></div>
</div>

<div class="modal fade" id="referenceConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Konfirmasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body" data-confirm-text>Apakah Anda yakin?</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" data-confirm-submit>Ya, lanjutkan</button></div>
        </div>
    </div>
</div>

@include('invoice-verification.components.file-preview-modal')
@include('invoice-verification.partials.rupiah-input')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabConfig = {
            vendors: { label: 'Tambah Vendor', target: '#vendorDrawer', placeholder: 'Search vendor name, NPWP, email...' },
            organization: { label: 'Tambah Department', target: '#departmentDrawer', placeholder: 'Search division, department, or LDAP code...' },
            ldap: { label: 'Tambah LDAP User', target: '#ldapDrawer', placeholder: 'Search name, email, role...' },
            memo: { label: 'Tambah Memo', target: '#memoDrawer', placeholder: 'Search memo number or title...' },
            agreements: { label: 'Tambah Agreement', target: '#agreementDrawer', placeholder: 'Search contract or vendor...' },
            templates: { label: 'Tambah Template', target: '#templateDrawer', placeholder: 'Search template name or code...' },
        };

        const searchInput = document.querySelector('[data-reference-search]');
        const statusFilter = document.querySelector('[data-reference-status]');
        const typeFilter = document.querySelector('[data-reference-type]');
        const filterForm = document.querySelector('[data-reference-filter-form]');
        const loading = document.querySelector('[data-reference-loading]');
        const addButtons = document.querySelectorAll('[data-master-add-button]');
        const divisionForm = document.getElementById('divisionForm');
        const divisionMethod = document.querySelector('[data-division-method]');
        const divisionSubmit = document.querySelector('[data-division-submit]');
        const departmentForm = document.getElementById('departmentForm');
        const departmentMethod = document.querySelector('[data-department-method]');
        const departmentSubmit = document.querySelector('[data-department-submit]');
        const vendorForm = document.getElementById('vendorForm');
        const vendorMethod = document.querySelector('[data-vendor-method]');
        const vendorSubmit = document.querySelector('[data-vendor-submit]');
        const ldapForm = document.getElementById('ldapForm');
        const ldapMethod = document.querySelector('[data-ldap-method]');
        const ldapSubmit = document.querySelector('[data-ldap-submit]');
        const agreementFileForm = document.getElementById('agreementFileForm');
        const agreementFileSubtitle = document.querySelector('[data-agreement-file-subtitle]');
        let activeTab = @json($activeTab);
        let pendingForm = null;
        let filterTimer = null;

        function updateContext() {
            const config = tabConfig[activeTab];
            addButtons.forEach(function (button) {
                const label = button.querySelector('[data-master-add-label]');
                if (label) label.textContent = config.label;
                button.setAttribute('data-bs-target', config.target);
            });
            if (searchInput) searchInput.placeholder = config.placeholder;
        }

        function setDivisionModeCreate() {
            if (!divisionForm) return;
            divisionForm.action = divisionForm.dataset.storeAction;
            divisionForm.reset();
            if (divisionMethod) divisionMethod.disabled = true;
            const status = divisionForm.querySelector('[data-division-field="isActive"]');
            if (status) status.value = '1';
            const title = document.getElementById('divisionDrawerLabel');
            if (title) title.textContent = 'Tambah Divisi';
            if (divisionSubmit) divisionSubmit.textContent = 'Simpan Divisi';
        }

        function setDivisionModeEdit(button) {
            if (!divisionForm) return;
            divisionForm.action = button.dataset.action;
            if (divisionMethod) divisionMethod.disabled = false;
            const title = document.getElementById('divisionDrawerLabel');
            if (title) title.textContent = 'Edit Divisi';
            if (divisionSubmit) divisionSubmit.textContent = 'Simpan Perubahan';

            const fields = {
                name: button.dataset.name || '',
                ldapCode: button.dataset.ldapCode || '',
                pettyCashCeiling: button.dataset.pettyCashCeiling || '',
                isActive: button.dataset.isActive || '1',
            };

            Object.entries(fields).forEach(function ([key, value]) {
                const input = divisionForm.querySelector('[data-division-field="' + key + '"]');
                if (input) input.value = value;
            });
            window.InvoiceRupiahInput?.formatElement(divisionForm.querySelector('[data-division-field="pettyCashCeiling"]'));
        }

        function setDepartmentModeCreate() {
            if (!departmentForm) return;
            departmentForm.action = departmentForm.dataset.storeAction;
            departmentForm.reset();
            if (departmentMethod) departmentMethod.disabled = true;
            const status = departmentForm.querySelector('[data-department-field="isActive"]');
            if (status) status.value = '1';
            const title = document.getElementById('departmentDrawerLabel');
            if (title) title.textContent = 'Tambah Department';
            if (departmentSubmit) departmentSubmit.textContent = 'Simpan Department';
        }

        function setDepartmentModeEdit(button) {
            if (!departmentForm) return;
            departmentForm.action = button.dataset.action;
            if (departmentMethod) departmentMethod.disabled = false;
            const title = document.getElementById('departmentDrawerLabel');
            if (title) title.textContent = 'Edit Department';
            if (departmentSubmit) departmentSubmit.textContent = 'Simpan Perubahan';

            const fields = {
                divisionId: button.dataset.divisionId || '',
                name: button.dataset.name || '',
                ldapCode: button.dataset.ldapCode || '',
                isActive: button.dataset.isActive || '1',
            };

            Object.entries(fields).forEach(function ([key, value]) {
                const input = departmentForm.querySelector('[data-department-field="' + key + '"]');
                if (input) input.value = value;
            });
        }

        function setVendorModeCreate() {
            if (!vendorForm) return;
            vendorForm.action = vendorForm.dataset.storeAction;
            vendorForm.reset();
            if (vendorMethod) vendorMethod.disabled = true;
            const title = document.getElementById('vendorDrawerLabel');
            if (title) title.textContent = 'Tambah Vendor';
            if (vendorSubmit) vendorSubmit.textContent = 'Simpan Vendor';
        }

        function setVendorModeEdit(button) {
            if (!vendorForm) return;
            vendorForm.action = button.dataset.action;
            if (vendorMethod) vendorMethod.disabled = false;
            const title = document.getElementById('vendorDrawerLabel');
            if (title) title.textContent = 'Edit Vendor';
            if (vendorSubmit) vendorSubmit.textContent = 'Simpan Perubahan';

            const fields = {
                name: button.dataset.name || '',
                npwp: button.dataset.npwp || '',
                contactName: button.dataset.contactName || '',
                contactEmail: button.dataset.contactEmail || '',
                contactPhone: button.dataset.contactPhone || '',
                bankName: button.dataset.bankName || '',
                accountNumber: button.dataset.accountNumber || '',
            };

            Object.entries(fields).forEach(function ([key, value]) {
                const input = vendorForm.querySelector('[data-vendor-field="' + key + '"]');
                if (input) input.value = value;
            });
        }

        function setLdapModeCreate() {
            if (!ldapForm) return;
            ldapForm.action = ldapForm.dataset.storeAction;
            ldapForm.reset();
            if (ldapMethod) ldapMethod.disabled = true;
            const active = ldapForm.querySelector('[data-ldap-field="isActive"]');
            if (active) active.checked = true;
            const title = document.getElementById('ldapDrawerLabel');
            if (title) title.textContent = 'Tambah LDAP User';
            if (ldapSubmit) ldapSubmit.textContent = 'Simpan Whitelist';
        }

        function setLdapModeEdit(button) {
            if (!ldapForm) return;
            ldapForm.action = button.dataset.action;
            if (ldapMethod) ldapMethod.disabled = false;
            const title = document.getElementById('ldapDrawerLabel');
            if (title) title.textContent = 'Edit LDAP User';
            if (ldapSubmit) ldapSubmit.textContent = 'Simpan Perubahan';

            const fields = {
                name: button.dataset.name || '',
                email: button.dataset.email || '',
                roleCode: button.dataset.roleCode || '',
                divisionId: button.dataset.divisionId || '',
                departmentId: button.dataset.departmentId || '',
                ldapUid: button.dataset.ldapUid || '',
                employeeNumber: button.dataset.employeeNumber || '',
            };

            Object.entries(fields).forEach(function ([key, value]) {
                const input = ldapForm.querySelector('[data-ldap-field="' + key + '"]');
                if (input) input.value = value;
            });

            const active = ldapForm.querySelector('[data-ldap-field="isActive"]');
            if (active) active.checked = button.dataset.isActive === '1';
        }

        function submitFilters(delay) {
            if (!filterForm) return;
            window.clearTimeout(filterTimer);
            filterTimer = window.setTimeout(function () {
                if (loading) loading.classList.add('is-visible');
                filterForm.submit();
            }, delay);
        }

        searchInput?.addEventListener('input', function () { submitFilters(420); });
        statusFilter?.addEventListener('change', function () { submitFilters(0); });
        typeFilter?.addEventListener('change', function () { submitFilters(0); });

        document.querySelectorAll('[data-detail-title]').forEach(function (button) {
            button.addEventListener('click', function () {
                const title = document.getElementById('vendorDetailDrawerLabel');
                const body = document.querySelector('[data-detail-content]');
                if (title) title.textContent = button.dataset.detailTitle || 'Detail Vendor';
                if (body) {
                    const lines = (button.dataset.detailBody || '').split('|');
                    body.innerHTML = lines.map(function (line) {
                        return '<div class="border-bottom py-3">' + line + '</div>';
                    }).join('');
                }
            });
        });

        addButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (activeTab === 'vendors') {
                    setVendorModeCreate();
                } else if (activeTab === 'organization') {
                    setDepartmentModeCreate();
                } else if (activeTab === 'ldap') {
                    setLdapModeCreate();
                }
            });
        });

        document.querySelectorAll('[data-division-create]').forEach(function (button) {
            button.addEventListener('click', setDivisionModeCreate);
        });

        document.querySelectorAll('[data-department-create]').forEach(function (button) {
            button.addEventListener('click', setDepartmentModeCreate);
        });

        document.querySelectorAll('[data-division-edit]').forEach(function (button) {
            button.addEventListener('click', function () {
                setDivisionModeEdit(button);
            });
        });

        document.querySelectorAll('[data-department-edit]').forEach(function (button) {
            button.addEventListener('click', function () {
                setDepartmentModeEdit(button);
            });
        });

        document.querySelectorAll('[data-vendor-edit]').forEach(function (button) {
            button.addEventListener('click', function () {
                setVendorModeEdit(button);
            });
        });

        document.querySelectorAll('[data-ldap-edit]').forEach(function (button) {
            button.addEventListener('click', function () {
                setLdapModeEdit(button);
            });
        });

        document.querySelectorAll('[data-agreement-file-upload]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (agreementFileForm) {
                    agreementFileForm.action = button.dataset.action || '#';
                    agreementFileForm.reset();
                }
                if (agreementFileSubtitle) {
                    agreementFileSubtitle.textContent = button.dataset.contractNumber || 'Lengkapi file untuk reference PO.';
                }
            });
        });

        const confirmModalElement = document.getElementById('referenceConfirmModal');
        const confirmModal = confirmModalElement ? new bootstrap.Modal(confirmModalElement) : null;
        const confirmText = document.querySelector('[data-confirm-text]');
        const confirmSubmit = document.querySelector('[data-confirm-submit]');

        document.querySelectorAll('[data-confirm-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                pendingForm = form;
                if (confirmText) confirmText.textContent = form.dataset.confirmMessage || 'Apakah Anda yakin?';
                confirmModal?.show();
            });
        });

        document.querySelectorAll('[data-confirm-message]:not(form)').forEach(function (button) {
            button.addEventListener('click', function () {
                pendingForm = null;
                if (confirmText) confirmText.textContent = button.dataset.confirmMessage || 'Action belum tersedia.';
                confirmModal?.show();
            });
        });

        confirmSubmit?.addEventListener('click', function () {
            if (pendingForm) {
                pendingForm.submit();
                return;
            }
            confirmModal?.hide();
        });

        updateContext();
    });
</script>
@endsection
