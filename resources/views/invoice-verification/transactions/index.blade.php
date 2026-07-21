@extends('layouts.vertical', ['subtitle' => 'Transactions'])

@section('css')
@include('invoice-verification.partials.table-ui')
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Invoice Verification', 'subtitle' => 'Transactions'])
@include('invoice-verification.partials.flash')

@php
    $isRevisionList = request('status') === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value;
    $isVendorPortal = auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::VENDOR);
@endphp

<div class="card iv-table-card">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">{{ $isVendorPortal ? 'Daftar Kontrak Vendor' : 'Daftar Transaksi' }}</h5>
                <p class="text-muted mb-0">
                    {{ $isVendorPortal ? 'Pilih kontrak yang belum selesai, lalu lengkapi invoice dan dokumen tagihan.' : 'Cari, filter, dan urutkan transaksi invoice collector.' }}
                </p>
            </div>
            @can('create', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class)
                <a href="{{ route('invoice-verification.transactions.create') }}" class="btn btn-success d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="solar:add-circle-outline" class="fs-18"></iconify-icon>
                    <span>Buat</span>
                </a>
            @endcan
        </div>
        @if ($isRevisionList)
            <div class="alert alert-warning border-0 mb-3">
                <div class="fw-semibold">Daftar Revisi</div>
                <div>Transaksi di bawah ini memiliki dokumen yang perlu diperbaiki atau diupload ulang oleh Vendor.</div>
            </div>
        @endif
        @if ($isVendorPortal && ! $linkedVendor)
            <div class="alert alert-warning border-0 mb-3">
                <div class="fw-semibold">Akun vendor belum terhubung.</div>
                <div>Email login Anda belum sama dengan contact email di master vendor. Hubungi Admin Divisi untuk memperbaiki data vendor.</div>
            </div>
        @endif
        <form method="GET" class="iv-table-toolbar" style="--iv-filter-count: {{ $isVendorPortal ? 0 : 2 }};">
            <div>
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><iconify-icon icon="solar:magnifer-outline" class="fs-18"></iconify-icon></span>
                    <input type="search" class="form-control" name="search" value="{{ $search }}" placeholder="{{ $isVendorPortal ? 'Nomor kontrak, judul, departemen' : 'Registrasi, vendor, judul' }}">
                </div>
            </div>
            @unless ($isVendorPortal)
                <div>
                    <label class="form-label">Jenis Transaksi</label>
                    <select name="transaction_type_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($transactionTypes as $type)
                            <option value="{{ $type->id }}" @selected($transactionTypeId === $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach ($statusOptions as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
            <button class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:filter-outline" class="fs-18"></iconify-icon>
                <span>Filter</span>
            </button>
            <a href="{{ route('invoice-verification.transactions.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:restart-outline" class="fs-18"></iconify-icon>
                <span>Reset</span>
            </a>
        </form>
    </div>
    <div class="card-body p-0">
        @if ($isVendorPortal)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 iv-table" style="--iv-table-min-width: 1120px;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Nomor Kontrak</th>
                            <th>Judul</th>
                            <th>Departemen</th>
                            <th>Nilai Kontrak</th>
                            <th>Periode</th>
                            <th>Status Upload</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vendorAgreements ?? [] as $agreement)
                            @php
                                $currentTransaction = $agreement->transactions->first();
                                $canUpload = ! $currentTransaction || in_array($currentTransaction->status?->value, [
                                    \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::DRAFT->value,
                                    \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value,
                                    \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::REVISION_IN_PROGRESS->value,
                                    \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::VENDOR_INPUT->value,
                                ], true);
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $agreement->contract_number }}</div>
                                    <div class="text-muted small">{{ $agreement->vendor?->name ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="text-truncate iv-cell-truncate" style="--iv-cell-width: 260px;" title="{{ $agreement->title }}">{{ $agreement->title ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="text-truncate iv-cell-truncate" style="--iv-cell-width: 220px;" title="{{ $agreement->department?->name ?? $agreement->division?->name ?? '-' }}">
                                        {{ $agreement->department?->name ?? $agreement->division?->name ?? '-' }}
                                    </div>
                                </td>
                                <td>Rp {{ number_format((float) $agreement->contract_value, 0, ',', '.') }}</td>
                                <td>
                                    <div>{{ $agreement->effective_date?->format('d M Y') ?? '-' }}</div>
                                    <div class="text-muted small">s/d {{ $agreement->expired_at?->format('d M Y') ?? '-' }}</div>
                                </td>
                                <td>
                                    @if ($currentTransaction)
                                        @include('invoice-verification.components.status-badge', ['value' => $currentTransaction->status])
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Belum upload</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="iv-actions">
                                        @if ($currentTransaction)
                                            @if ($canUpload)
                                                <a href="{{ route('invoice-verification.transactions.documents.show', $currentTransaction) }}" class="btn btn-sm btn-primary">
                                                    {{ $currentTransaction->status?->value === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value ? 'Revisi' : 'Upload' }}
                                                </a>
                                            @endif
                                            <a href="{{ route('invoice-verification.transactions.show', $currentTransaction) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                        @else
                                            <form method="POST" action="{{ route('invoice-verification.transactions.agreements.start', $agreement) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-primary">Upload Tagihan</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada kontrak aktif untuk akun vendor ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-top">
                {{ ($vendorAgreements ?? collect())->links() }}
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 iv-table" style="--iv-table-min-width: 1180px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.transactions.index', 'column' => 'registration_number', 'label' => 'Registrasi'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.transactions.index', 'column' => 'transaction_type', 'label' => 'Jenis'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.transactions.index', 'column' => 'vendor', 'label' => 'Vendor'])</th>
                        <th>Invoice</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.transactions.index', 'column' => 'status', 'label' => 'Status'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.transactions.index', 'column' => 'current_step', 'label' => 'Step'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.transactions.index', 'column' => 'created_at', 'label' => 'Dibuat'])</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $transaction->registration_number }}</div>
                                <div class="text-muted small text-truncate iv-cell-truncate" title="{{ $transaction->title }}">{{ $transaction->title }}</div>
                            </td>
                            <td>{{ $transaction->transactionType?->code?->value ?? '-' }}</td>
                            <td><div class="text-truncate iv-cell-truncate" style="--iv-cell-width: 190px;" title="{{ $transaction->vendor?->name ?? $transaction->owner?->name ?? '-' }}">{{ $transaction->vendor?->name ?? $transaction->owner?->name ?? '-' }}</div></td>
                            <td>{{ $transaction->invoiceMetadata?->invoice_number ?? '-' }}</td>
                            <td>@include('invoice-verification.components.status-badge', ['value' => $transaction->status])</td>
                            <td><div class="text-truncate iv-cell-truncate" style="--iv-cell-width: 180px;" title="{{ $transaction->current_step?->label() }}">{{ $transaction->current_step?->label() }}</div></td>
                            <td>{{ $transaction->created_at?->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                <div class="iv-actions">
                                    @if (in_array($transaction->status?->value, ['DRAFT', 'VENDOR_INPUT'], true))
                                        @can('uploadDocuments', $transaction)
                                            <a href="{{ route('invoice-verification.transactions.documents.show', $transaction) }}" class="btn btn-sm btn-primary">Upload</a>
                                        @endcan
                                    @endif
                                    @if ($transaction->status?->value === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value)
                                        @can('uploadDocuments', $transaction)
                                            <a href="{{ route('invoice-verification.transactions.documents.show', $transaction) }}" class="btn btn-sm btn-warning">Revisi</a>
                                        @endcan
                                    @endif
                                    <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-top">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
