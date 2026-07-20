@extends('layouts.vertical', ['subtitle' => 'Finance Queue'])

@section('css')
<style>
    .finance-queue-table {
        min-width: 1260px;
    }

    .finance-queue-table th {
        font-size: .78rem;
        letter-spacing: .02em;
        text-transform: uppercase;
        color: #64748b;
        white-space: nowrap;
    }

    .finance-queue-table td {
        vertical-align: middle;
        padding-top: .65rem;
        padding-bottom: .65rem;
        line-height: 1.25;
    }

    .finance-sort-link {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        color: inherit;
        text-decoration: none;
    }

    .finance-sort-link:hover {
        color: var(--bs-primary);
    }

    .finance-toolbar {
        display: grid;
        grid-template-columns: minmax(280px, 1fr) 190px 170px auto auto;
        gap: .75rem;
        align-items: end;
    }

    .finance-toolbar .form-label {
        font-size: .75rem;
        margin-bottom: .25rem;
    }

    .finance-transaction-title {
        max-width: 420px;
    }

    .finance-vendor-cell {
        max-width: 210px;
    }

    .finance-proof-link {
        max-width: 230px;
    }

    .finance-action-panel {
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
        min-width: 410px;
    }

    .finance-action-panel .form-control {
        min-height: 34px;
    }

    .finance-schedule-form,
    .finance-proof-form {
        display: grid;
        gap: .5rem;
    }

    .finance-schedule-form {
        grid-template-columns: minmax(205px, 1fr) auto;
    }

    .finance-proof-form {
        grid-template-columns: minmax(230px, 1fr) auto;
    }

    .finance-paid-form {
        flex: 0 0 auto;
    }

    @media (max-width: 1199.98px) {
        .finance-toolbar {
            grid-template-columns: 1fr 1fr;
        }

        .finance-action-panel {
            justify-content: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .finance-toolbar {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
@php
    $sortIcon = function (string $column) use ($sort, $direction) {
        if ($sort !== $column) {
            return 'solar:alt-arrow-down-outline';
        }

        return $direction === 'asc' ? 'solar:arrow-up-outline' : 'solar:arrow-down-outline';
    };

    $sortUrl = function (string $column) use ($sort, $direction) {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('invoice-verification.finance.index', array_merge(request()->except(['sort', 'direction', 'page']), [
            'sort' => $column,
            'direction' => $nextDirection,
        ]));
    };
@endphp

@include('layouts.partials.page-title', ['title' => 'Finance Queue', 'subtitle' => 'Scheduling Payment dan Paid'])
@include('invoice-verification.partials.flash')

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h5 class="card-title mb-1">Transaksi Pembayaran</h5>
                <p class="text-muted mb-0">Transaksi Received dapat dijadwalkan, lalu bukti transfer diunggah sebelum status Paid.</p>
            </div>
            <span class="badge bg-light text-dark border">{{ $transactions->total() }} transaksi</span>
        </div>
    </div>
    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('invoice-verification.finance.index') }}" class="finance-toolbar">
            <div>
                <label for="finance-search" class="form-label text-muted">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><iconify-icon icon="solar:magnifer-outline" class="fs-18"></iconify-icon></span>
                    <input id="finance-search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Nomor, vendor, atau judul transaksi">
                </div>
            </div>
            <div>
                <label for="finance-status" class="form-label text-muted">Status</label>
                <select id="finance-status" name="status" class="form-select">
                    <option value="">Semua status</option>
                    <option value="{{ \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::RECEIVED->value }}" @selected($status === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::RECEIVED->value)>Received</option>
                    <option value="{{ \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::SCHEDULING_PAYMENT->value }}" @selected($status === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::SCHEDULING_PAYMENT->value)>Scheduling Payment</option>
                </select>
            </div>
            <div>
                <label for="finance-proof" class="form-label text-muted">Bukti Transfer</label>
                <select id="finance-proof" name="proof" class="form-select">
                    <option value="">Semua bukti</option>
                    <option value="uploaded" @selected($proof === 'uploaded')>Sudah upload</option>
                    <option value="missing" @selected($proof === 'missing')>Belum ada</option>
                </select>
            </div>
            <button class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:filter-outline" class="fs-18"></iconify-icon>
                <span>Filter</span>
            </button>
            <a href="{{ route('invoice-verification.finance.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:restart-outline" class="fs-18"></iconify-icon>
                <span>Reset</span>
            </a>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 finance-queue-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 28%;">
                            <a href="{{ $sortUrl('registration_number') }}" class="finance-sort-link">
                                <span>Transaksi</span>
                                <iconify-icon icon="{{ $sortIcon('registration_number') }}" class="fs-14"></iconify-icon>
                            </a>
                        </th>
                        <th style="width: 15%;">
                            <a href="{{ $sortUrl('vendor') }}" class="finance-sort-link">
                                <span>Vendor</span>
                                <iconify-icon icon="{{ $sortIcon('vendor') }}" class="fs-14"></iconify-icon>
                            </a>
                        </th>
                        <th style="width: 11%;">
                            <a href="{{ $sortUrl('status') }}" class="finance-sort-link">
                                <span>Status</span>
                                <iconify-icon icon="{{ $sortIcon('status') }}" class="fs-14"></iconify-icon>
                            </a>
                        </th>
                        <th style="width: 10%;">
                            <a href="{{ $sortUrl('scheduled_payment_at') }}" class="finance-sort-link">
                                <span>Jadwal</span>
                                <iconify-icon icon="{{ $sortIcon('scheduled_payment_at') }}" class="fs-14"></iconify-icon>
                            </a>
                        </th>
                        <th style="width: 14%;">
                            <a href="{{ $sortUrl('payment_proof_file_name') }}" class="finance-sort-link">
                                <span>Bukti Transfer</span>
                                <iconify-icon icon="{{ $sortIcon('payment_proof_file_name') }}" class="fs-14"></iconify-icon>
                            </a>
                        </th>
                        <th class="text-end pe-4" style="width: 16%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="fw-semibold text-body">
                                    {{ $transaction->registration_number }}
                                </a>
                                <div class="text-muted small text-truncate finance-transaction-title" title="{{ $transaction->title }}">
                                    {{ $transaction->title }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium text-truncate finance-vendor-cell" title="{{ $transaction->vendor?->name ?? $transaction->owner?->name ?? '-' }}">
                                    {{ $transaction->vendor?->name ?? $transaction->owner?->name ?? '-' }}
                                </div>
                                @if ($transaction->owner && ! $transaction->vendor)
                                    <div class="text-muted small text-truncate finance-vendor-cell" title="{{ $transaction->owner->department?->name ?? 'Internal' }}">
                                        {{ $transaction->owner->department?->name ?? 'Internal' }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $transaction->status?->badgeClass() }} px-2 py-1">{{ $transaction->status?->label() }}</span>
                            </td>
                            <td>
                                @if ($transaction->scheduled_payment_at)
                                    <div class="fw-medium">{{ $transaction->scheduled_payment_at->format('d M Y') }}</div>
                                    <div class="text-muted small">{{ $transaction->scheduled_payment_at->format('H:i') }}</div>
                                @else
                                    <span class="text-muted">Belum dijadwalkan</span>
                                @endif
                            </td>
                            <td>
                                @if ($transaction->payment_proof_file_path)
                                    <a href="{{ route('invoice-verification.finance.payment-proof.preview', $transaction) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 finance-proof-link text-truncate" target="_blank" title="{{ $transaction->payment_proof_file_name }}">
                                        <iconify-icon icon="solar:file-text-outline" class="fs-16 flex-shrink-0"></iconify-icon>
                                        <span class="text-truncate">{{ $transaction->payment_proof_file_name }}</span>
                                    </a>
                                @else
                                    <span class="text-muted">Belum ada</span>
                                @endif
                            </td>
                            <td class="pe-4">
                                @if ($transaction->status === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::RECEIVED)
                                    <form method="POST" action="{{ route('invoice-verification.finance.schedule', $transaction) }}" class="finance-action-panel finance-schedule-form">
                                        @csrf
                                        <input type="datetime-local" name="scheduled_payment_at" class="form-control form-control-sm" required>
                                        <button class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1">
                                            <iconify-icon icon="solar:calendar-add-outline" class="fs-16"></iconify-icon>
                                            <span>Jadwalkan</span>
                                        </button>
                                    </form>
                                @elseif ($transaction->status === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::SCHEDULING_PAYMENT)
                                    <div class="finance-action-panel">
                                        <form method="POST" action="{{ route('invoice-verification.finance.payment-proof', $transaction) }}" enctype="multipart/form-data" class="finance-proof-form flex-grow-1">
                                            @csrf
                                            <input type="file" name="payment_proof" class="form-control form-control-sm" required>
                                            <button class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center gap-1">
                                                <iconify-icon icon="solar:upload-outline" class="fs-16"></iconify-icon>
                                                <span>Upload</span>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('invoice-verification.finance.paid', $transaction) }}" class="finance-paid-form">
                                            @csrf
                                            <button class="btn btn-sm btn-success d-inline-flex align-items-center justify-content-center gap-1" @disabled(! $transaction->payment_proof_file_path)>
                                                <iconify-icon icon="solar:check-circle-outline" class="fs-16"></iconify-icon>
                                                <span>Paid</span>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <iconify-icon icon="solar:wallet-money-outline" class="fs-32 d-block mb-2"></iconify-icon>
                                Tidak ada transaksi yang sedang menunggu proses finance.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-top">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
