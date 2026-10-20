@extends('layouts.vertical', ['subtitle' => 'Dashboard'])

@section('content')
{{-- Soft, minimal dashboard shell aligned with the primary navigation. --}}
@php
    $periodChange = $analytics['insights']['period_change'];
    $periodTone = $periodChange >= 0 ? 'success' : 'danger';
    $pipelineMetrics = $analytics['pipeline_summary'];
    $pipelineTotalAmount = collect($pipelineMetrics)->sum('amount');
    $formatRupiah = fn (float|int|string|null $value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
@endphp

<style>
    .invoice-dashboard {
        --iv-surface: #ffffff;
        --iv-soft: #f7f8fa;
        --iv-muted: #748096;
        --iv-border: #e9ebef;
        --iv-shadow: 0 1px 2px rgba(16, 24, 40, .025), 0 8px 24px rgba(16, 24, 40, .035);
        color: #273044;
    }

    .invoice-dashboard .dashboard-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--iv-border);
        border-radius: 16px;
        background: var(--iv-surface);
        box-shadow: var(--iv-shadow);
    }

    .invoice-dashboard .dashboard-hero::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: var(--bs-primary);
        pointer-events: none;
    }

    .invoice-dashboard .hero-kicker,
    .invoice-dashboard .metric-label,
    .invoice-dashboard .chart-kicker {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .075em;
        text-transform: uppercase;
    }

    .invoice-dashboard .dashboard-hero h2 {
        color: #252d3d;
        font-size: clamp(1.5rem, 2.4vw, 2rem);
        letter-spacing: -.025em;
    }

    .invoice-dashboard .dashboard-hero p,
    .invoice-dashboard .card-header p {
        color: var(--iv-muted) !important;
    }

    .invoice-dashboard .metric-card,
    .invoice-dashboard .analytics-card,
    .invoice-dashboard .queue-panel,
    .invoice-dashboard .table-panel,
    .invoice-dashboard .pipeline-card {
        border: 1px solid var(--iv-border);
        border-radius: 14px;
        background: var(--iv-surface);
        box-shadow: var(--iv-shadow);
    }

    .invoice-dashboard .metric-card {
        position: relative;
        overflow: hidden;
        min-height: 154px;
    }

    .invoice-dashboard .metric-icon {
        position: absolute;
        top: 24px;
        right: 24px;
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
    }

    .invoice-dashboard .metric-content {
        min-width: 0;
        padding-right: 58px;
    }

    .invoice-dashboard .metric-value {
        color: #1f2a4d;
        line-height: 1.05;
        letter-spacing: 0;
        overflow-wrap: anywhere;
        word-break: normal;
    }

    .invoice-dashboard .metric-value.is-money {
        font-size: 2rem;
        max-width: 100%;
    }

    .invoice-dashboard .analytics-card .card-header,
    .invoice-dashboard .queue-panel .card-header,
    .invoice-dashboard .table-panel .card-header,
    .invoice-dashboard .pipeline-card .card-header {
        border-bottom: 1px solid var(--iv-border);
        background: transparent;
        padding: 20px 22px 16px;
    }

    .invoice-dashboard .analytics-card .card-body,
    .invoice-dashboard .queue-panel .card-body,
    .invoice-dashboard .table-panel .card-body,
    .invoice-dashboard .pipeline-card .card-body {
        padding: 20px 22px 22px;
    }

    .invoice-dashboard .card-title {
        color: #30384a;
        font-weight: 700;
        letter-spacing: -.01em;
    }

    .invoice-dashboard .apex-charts {
        min-height: 260px;
    }

    .invoice-dashboard .stat-strip {
        display: flex;
        justify-content: flex-end;
    }

    .invoice-dashboard .hero-total-card {
        border: 1px solid var(--iv-border);
        border-radius: 14px;
        background: var(--iv-soft);
        padding: 18px 20px;
        min-width: 220px;
        box-shadow: none;
    }

    .invoice-dashboard .hero-total-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }

    .invoice-dashboard .pipeline-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .invoice-dashboard .pipeline-item {
        position: relative;
        min-height: 136px;
        overflow: hidden;
        border: 1px solid var(--iv-border);
        border-radius: 12px;
        background: #fbfcfd;
        padding: 16px;
        transition: border-color .2s ease, background-color .2s ease;
    }

    .invoice-dashboard .pipeline-item:hover {
        border-color: #dfe2e7;
        background: #fff;
    }

    .invoice-dashboard .pipeline-icon {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }

    .invoice-dashboard .pipeline-count {
        color: #293246;
        font-size: 1.65rem;
        line-height: 1;
        letter-spacing: 0;
    }

    .invoice-dashboard .pipeline-amount {
        color: var(--iv-muted);
        font-size: .78rem;
        font-weight: 600;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .invoice-dashboard .pipeline-item .badge,
    .invoice-dashboard .pipeline-card > .card-header .badge,
    .invoice-dashboard .analytics-card .badge {
        border: 1px solid rgba(100, 116, 139, .08);
        border-radius: 999px;
        font-weight: 600;
    }

    .invoice-dashboard .clean-table thead th {
        border-top: 0;
        border-bottom: 1px solid var(--iv-border);
        color: #6b7280;
        font-size: .74rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        background: #fafbfc;
    }

    .invoice-dashboard .clean-table tbody td {
        border-color: var(--iv-border);
        padding-top: 14px;
        padding-bottom: 14px;
        vertical-align: middle;
    }

    .invoice-dashboard .clean-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .invoice-dashboard .btn {
        border-radius: 9px;
        font-weight: 600;
    }

    .invoice-dashboard .queue-item {
        border: 1px solid var(--iv-border);
        border-radius: 12px;
        background: var(--iv-soft);
        padding: 12px 14px;
    }

    .invoice-dashboard .queue-step-dot {
        width: 10px;
        height: 10px;
        flex: 0 0 10px;
        border-radius: 50%;
        background: var(--bs-primary);
        box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), .12);
        margin-top: 6px;
    }

    @media (max-width: 991.98px) {
        .invoice-dashboard .pipeline-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .invoice-dashboard .pipeline-grid {
            grid-template-columns: 1fr;
        }

        .invoice-dashboard .dashboard-hero,
        .invoice-dashboard .metric-card,
        .invoice-dashboard .analytics-card,
        .invoice-dashboard .queue-panel,
        .invoice-dashboard .table-panel,
        .invoice-dashboard .pipeline-card {
            border-radius: 14px;
        }

        .invoice-dashboard .metric-content {
            padding-right: 52px;
        }
    }
</style>

<div class="invoice-dashboard">
    <div class="dashboard-hero p-4 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-xl-7">
                <span class="hero-kicker text-primary">Sistem Verifikasi</span>
                <h2 class="mt-2 mb-2 fw-bold">Invoice Verification Analytics</h2>
                <p class="text-muted mb-0">Ringkasan performa transaksi, nominal pembayaran, dan progress operasional dalam satu dashboard.</p>
            </div>
            <div class="col-xl-5">
                <div class="stat-strip">
                    <div class="hero-total-card">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div class="text-muted small fw-semibold">Total Transaksi</div>
                                <div class="h2 fw-bold mb-0">{{ $summary['transactions_total'] }}</div>
                            </div>
                            <span class="hero-total-icon bg-primary-subtle text-primary">
                                <iconify-icon icon="solar:bill-list-outline" class="fs-24"></iconify-icon>
                            </span>
                        </div>
                        <div class="text-muted small">{{ $formatRupiah($pipelineTotalAmount) }} total nominal</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card pipeline-card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="chart-kicker text-primary mb-1">Pipeline Transaksi</div>
                <h5 class="card-title mb-1">Jumlah dan nominal per status</h5>
                <p class="text-muted mb-0">Ringkasan posisi transaksi dari Draft sampai Paid.</p>
            </div>
            <span class="badge bg-light text-dark px-3 py-2">{{ $analytics['insights']['completion_rate'] }}% selesai</span>
        </div>
        <div class="card-body">
            <div class="pipeline-grid">
                @foreach ($pipelineMetrics as $metric)
                    <div class="pipeline-item text-{{ $metric['tone'] }}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <span class="pipeline-icon bg-{{ $metric['tone'] }}-subtle text-{{ $metric['tone'] }}">
                                <iconify-icon icon="{{ $metric['icon'] }}" class="fs-20"></iconify-icon>
                            </span>
                            <span class="badge bg-{{ $metric['tone'] }}-subtle text-{{ $metric['tone'] }}">{{ $metric['count'] }} trx</span>
                        </div>
                        <div class="metric-label text-{{ $metric['tone'] }} mb-2">{{ $metric['label'] }}</div>
                        <div class="pipeline-count fw-bold mb-2">{{ $metric['count'] }}</div>
                        <div class="pipeline-amount">{{ $formatRupiah($metric['amount']) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card analytics-card h-100 mb-0">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <div class="chart-kicker text-primary mb-1">Trend Transaksi</div>
                        <h5 class="card-title mb-1">Volume transaksi 6 bulan terakhir</h5>
                        <p class="text-muted mb-0">Menggabungkan transaksi PPA, SPU, SPUK, dan Kas Kecil.</p>
                    </div>
                    <span class="badge bg-{{ $periodTone }}-subtle text-{{ $periodTone }} px-3 py-2">
                        {{ $periodChange >= 0 ? '+' : '' }}{{ $periodChange }}% vs periode sebelumnya
                    </span>
                </div>
                <div class="card-body">
                    <div id="iv-transaction-trend" class="apex-charts"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card analytics-card h-100 mb-0">
                <div class="card-header">
                    <div class="chart-kicker text-primary mb-1">Distribusi Status</div>
                    <h5 class="card-title mb-1">Komposisi transaksi aktif</h5>
                    <p class="text-muted mb-0">Status real-time berdasarkan transaksi tersimpan.</p>
                </div>
                <div class="card-body">
                    <div id="iv-status-donut" class="apex-charts"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card analytics-card h-100 mb-0">
                <div class="card-header">
                    <div class="chart-kicker text-primary mb-1">Nominal Mingguan</div>
                    <h5 class="card-title mb-1">Nilai transaksi per minggu</h5>
                    <p class="text-muted mb-0">Akumulasi nominal transaksi pada 8 minggu terakhir.</p>
                </div>
                <div class="card-body">
                    <div id="iv-amount-weekly" class="apex-charts"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card analytics-card h-100 mb-0">
                <div class="card-header">
                    <div class="chart-kicker text-primary mb-1">Nominal Bulanan</div>
                    <h5 class="card-title mb-1">Nilai transaksi per bulan</h5>
                    <p class="text-muted mb-0">Akumulasi nominal transaksi pada 12 bulan terakhir.</p>
                </div>
                <div class="card-body">
                    <div id="iv-amount-monthly" class="apex-charts"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-xl-8">
            <div class="card table-panel mb-0">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="card-title mb-1">Transaksi Terbaru</h5>
                        <p class="text-muted mb-0">Ringkas, bersih, dan fokus ke progres transaksi.</p>
                    </div>
                    <a href="{{ route('invoice-verification.transactions.index') }}" class="btn btn-sm btn-primary">
                        <iconify-icon icon="solar:list-arrow-right-outline" class="align-middle me-1"></iconify-icon>
                        Lihat Semua
                    </a>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table clean-table table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Registrasi</th>
                                    <th>Jenis</th>
                                    <th>Vendor</th>
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
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 190px;">{{ $transaction->vendor?->name ?? '-' }}</span>
                                        </td>
                                        <td>@include('invoice-verification.components.status-badge', ['value' => $transaction->status])</td>
                                        <td class="text-end">
                                            <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada transaksi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card analytics-card h-100 mb-0">
                <div class="card-header">
                    <div class="chart-kicker text-primary mb-1">Nominal Tahunan</div>
                    <h5 class="card-title mb-1">Nilai transaksi per tahun</h5>
                    <p class="text-muted mb-0">Akumulasi nominal transaksi pada 5 tahun terakhir.</p>
                </div>
                <div class="card-body">
                    <div id="iv-amount-yearly" class="apex-charts"></div>
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
