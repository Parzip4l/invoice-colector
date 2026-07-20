@extends('layouts.vertical', ['subtitle' => 'Vendor Dashboard'])

@section('content')
@php
    $cards = [
        ['label' => 'Total Tagihan', 'value' => $summary['total'], 'icon' => 'solar:bill-list-outline', 'tone' => 'primary', 'meta' => 'Semua transaksi Anda'],
        ['label' => 'Perlu Revisi', 'value' => $summary['not_approved'], 'icon' => 'solar:danger-triangle-outline', 'tone' => 'danger', 'meta' => 'Not Approved'],
        ['label' => 'Dalam Proses', 'value' => $summary['submitted'] + $summary['in_review'] + $summary['received'], 'icon' => 'solar:hourglass-line-outline', 'tone' => 'warning', 'meta' => 'Submitted, In Review, Received'],
        ['label' => 'Paid', 'value' => $summary['paid'], 'icon' => 'solar:check-circle-outline', 'tone' => 'success', 'meta' => $summary['completion_rate'] . '% selesai'],
    ];
@endphp

<style>
    .vendor-dashboard {
        --vd-border: rgba(33, 37, 41, .075);
        --vd-shadow: 0 12px 32px rgba(27, 36, 54, .08);
    }

    .vendor-dashboard .vendor-hero,
    .vendor-dashboard .vendor-card,
    .vendor-dashboard .vendor-panel {
        border: 1px solid var(--vd-border);
        border-radius: 16px;
        background: #fff;
        box-shadow: var(--vd-shadow);
    }

    .vendor-dashboard .vendor-hero {
        overflow: hidden;
        background: linear-gradient(135deg, rgba(226, 26, 26, .10), rgba(255, 255, 255, .96) 52%, rgba(22, 163, 74, .08));
    }

    .vendor-dashboard .vendor-kicker {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .vendor-dashboard .vendor-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
    }

    .vendor-dashboard .vendor-panel .card-header {
        border-bottom: 1px solid var(--vd-border);
        background: transparent;
        padding: 18px 20px 12px;
    }

    .vendor-dashboard .vendor-panel .card-body {
        padding: 18px 20px 20px;
    }

    .vendor-dashboard .apex-charts {
        min-height: 260px;
    }

    .vendor-dashboard .compact-table td {
        padding-top: .65rem;
        padding-bottom: .65rem;
        vertical-align: middle;
    }
</style>

<div class="vendor-dashboard">
    <div class="vendor-hero p-4 p-xl-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-xl-8">
                <div class="vendor-kicker text-primary mb-2">Vendor Portal</div>
                <h2 class="fw-bold mb-2">Tracking Tagihan Vendor</h2>
                <p class="text-muted mb-0">Pantau tagihan, revisi dokumen, status verifikasi, dan pembayaran untuk transaksi milik Anda.</p>
            </div>
            <div class="col-xl-4">
                <div class="bg-white rounded-3 p-3 border">
                    <div class="text-muted small">Vendor</div>
                    <div class="h5 mb-1">{{ $vendor?->name ?? auth()->user()?->name }}</div>
                    <div class="text-muted small">{{ auth()->user()?->email }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($cards as $card)
            <div class="col-md-6 col-xl-3">
                <div class="card vendor-card h-100 mb-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="vendor-kicker text-{{ $card['tone'] }}">{{ $card['label'] }}</div>
                                <h2 class="fw-bold mt-2 mb-1">{{ $card['value'] }}</h2>
                                <div class="text-muted small">{{ $card['meta'] }}</div>
                            </div>
                            <span class="vendor-icon bg-{{ $card['tone'] }}-subtle text-{{ $card['tone'] }}">
                                <iconify-icon icon="{{ $card['icon'] }}" class="fs-24"></iconify-icon>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card vendor-panel h-100 mb-0">
                <div class="card-header">
                    <div class="vendor-kicker text-primary mb-1">Trend Tagihan</div>
                    <h5 class="card-title mb-1">Jumlah transaksi 6 bulan terakhir</h5>
                    <p class="text-muted mb-0">Hanya menghitung transaksi milik vendor login.</p>
                </div>
                <div class="card-body">
                    <div id="iv-transaction-trend" class="apex-charts"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card vendor-panel h-100 mb-0">
                <div class="card-header">
                    <div class="vendor-kicker text-primary mb-1">Status Tagihan</div>
                    <h5 class="card-title mb-1">Distribusi status</h5>
                    <p class="text-muted mb-0">Komposisi status transaksi Anda.</p>
                </div>
                <div class="card-body">
                    <div id="iv-status-donut" class="apex-charts"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card vendor-panel h-100 mb-0">
                <div class="card-header">
                    <div class="vendor-kicker text-primary mb-1">Nominal Tagihan</div>
                    <h5 class="card-title mb-1">Nilai transaksi per bulan</h5>
                    <p class="text-muted mb-0">Total: Rp {{ number_format((float) $summary['amount_total'], 0, ',', '.') }}</p>
                </div>
                <div class="card-body">
                    <div id="iv-amount-monthly" class="apex-charts"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card vendor-panel h-100 mb-0">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="card-title mb-1">Transaksi Terbaru</h5>
                        <p class="text-muted mb-0">Pantau progress dan lanjutkan revisi bila diperlukan.</p>
                    </div>
                    <a href="{{ route('invoice-verification.transactions.index') }}" class="btn btn-sm btn-primary">Lihat Semua</a>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table compact-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Registrasi</th>
                                    <th>Jenis</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentTransactions as $transaction)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $transaction->registration_number }}</div>
                                            <div class="text-muted small text-truncate" style="max-width: 280px;">{{ $transaction->title }}</div>
                                        </td>
                                        <td>{{ $transaction->transactionType?->name }}</td>
                                        <td>@include('invoice-verification.components.status-badge', ['value' => $transaction->status])</td>
                                        <td class="text-end">
                                            <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Belum ada transaksi vendor.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.invoiceDashboardAnalytics = @json($analytics);
</script>
@endsection

@section('scripts')
@vite(['resources/js/pages/invoice-verification-dashboard.js'])
@endsection
