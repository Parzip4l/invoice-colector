@extends('layouts.vertical', ['subtitle' => 'Dashboard'])

@section('content')
{{-- Soft, modern, flat dashboard shell with zero gradients and zero awkward whitespace. --}}
@php
    $periodChange = $analytics['insights']['period_change'];
    $pipelineMetrics = $analytics['pipeline_summary'];
    $pipelineTotalAmount = collect($pipelineMetrics)->sum('amount');
    $formatRupiah = fn (float|int|string|null $value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
@endphp

<style>
    .invoice-dashboard {
        --iv-surface: #ffffff;
        --iv-soft-bg: #f8fafc;
        --iv-muted: #64748b;
        --iv-border: #e2e8f0;
        --iv-border-subtle: #f1f5f9;
        --iv-text-dark: #1e293b;
        --iv-text-body: #334155;
        --iv-shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.03);
        --iv-shadow-md: 0 4px 14px rgba(15, 23, 42, 0.04);
        color: var(--iv-text-body);
        font-size: 13px;
    }

    /* Soft Modern Status Color Systems (Pure solid pastel tints, NO gradients) */
    .invoice-dashboard .status-draft {
        --status-accent: #64748b;
        --status-bg: #f8fafc;
        --status-icon-bg: #f1f5f9;
        --status-border: #e2e8f0;
        --status-pill-bg: #f1f5f9;
        --status-pill-text: #475569;
    }
    .invoice-dashboard .status-submitted {
        --status-accent: #e21a1a;
        --status-bg: #fff5f5;
        --status-icon-bg: #fee2e2;
        --status-border: #fecaca;
        --status-pill-bg: #fee2e2;
        --status-pill-text: #b91c1c;
    }
    .invoice-dashboard .status-in_review {
        --status-accent: #d97706;
        --status-bg: #fffbeb;
        --status-icon-bg: #fef3c7;
        --status-border: #fde68a;
        --status-pill-bg: #fef3c7;
        --status-pill-text: #92400e;
    }
    .invoice-dashboard .status-not_approved {
        --status-accent: #be123c;
        --status-bg: #fff1f2;
        --status-icon-bg: #ffe4e6;
        --status-border: #fecdd3;
        --status-pill-bg: #ffe4e6;
        --status-pill-text: #9f1239;
    }
    .invoice-dashboard .status-received {
        --status-accent: #7c3aed;
        --status-bg: #f5f3ff;
        --status-icon-bg: #ede9fe;
        --status-border: #ddd6fe;
        --status-pill-bg: #ede9fe;
        --status-pill-text: #5b21b6;
    }
    .invoice-dashboard .status-completed {
        --status-accent: #059669;
        --status-bg: #ecfdf5;
        --status-icon-bg: #d1fae5;
        --status-border: #a7f3d0;
        --status-pill-bg: #d1fae5;
        --status-pill-text: #065f46;
    }

    /* Soft modern badge overrides within the dashboard */
    .invoice-dashboard .badge.bg-primary-subtle {
        background-color: #fff5f5 !important;
        color: #b91c1c !important;
        border: 1px solid #fecaca !important;
    }
    .invoice-dashboard .badge.bg-dark-subtle {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        border: 1px solid #fde68a !important;
    }
    .invoice-dashboard .badge.bg-danger-subtle {
        background-color: #ffe4e6 !important;
        color: #9f1239 !important;
        border: 1px solid #fecdd3 !important;
    }
    .invoice-dashboard .badge.bg-info-subtle {
        background-color: #ede9fe !important;
        color: #5b21b6 !important;
        border: 1px solid #ddd6fe !important;
    }
    .invoice-dashboard .badge.bg-success-subtle {
        background-color: #d1fae5 !important;
        color: #065f46 !important;
        border: 1px solid #a7f3d0 !important;
    }
    .invoice-dashboard .badge.bg-secondary-subtle {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
        border: 1px solid #e2e8f0 !important;
    }
    .invoice-dashboard .badge.bg-warning-subtle {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        border: 1px solid #fde68a !important;
    }

    /* Dashboard Hero */
    .invoice-dashboard .dashboard-hero {
        background: var(--iv-surface);
        border: 1px solid var(--iv-border);
        border-radius: 14px;
        box-shadow: var(--iv-shadow-sm);
        padding: 16px 20px;
    }

    .invoice-dashboard .hero-kicker-badge {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 3px 10px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .invoice-dashboard .hero-kicker-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #e21a1a;
        display: inline-block;
    }

    .invoice-dashboard .hero-heading {
        color: var(--iv-text-dark);
        font-size: clamp(1.2rem, 1.8vw, 1.5rem);
        letter-spacing: -.02em;
    }

    .invoice-dashboard .hero-lead {
        color: var(--iv-muted);
        font-size: 12.5px;
        line-height: 1.45;
        max-width: 620px;
    }

    .invoice-dashboard .hero-metric-tile {
        background: var(--iv-soft-bg);
        border: 1px solid var(--iv-border);
        border-radius: 11px;
        padding: 10px 14px;
        flex: 1;
        min-width: 170px;
        transition: border-color .15s ease;
    }

    .invoice-dashboard .hero-metric-tile:hover {
        border-color: #cbd5e1;
    }

    .invoice-dashboard .hero-metric-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--status-icon-bg, #f1f5f9);
        color: var(--status-accent, #64748b);
        flex-shrink: 0;
    }

    .invoice-dashboard .hero-metric-title {
        color: var(--iv-muted);
        font-size: 10.5px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .invoice-dashboard .hero-metric-val {
        color: var(--iv-text-dark);
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .invoice-dashboard .hero-metric-val.is-money {
        font-size: 1.05rem;
    }

    .invoice-dashboard .hero-metric-unit {
        font-size: 11px;
        font-weight: 500;
        color: var(--iv-muted);
    }

    .invoice-dashboard .hero-metric-sub {
        font-size: 10.5px;
        color: var(--iv-muted);
        margin-top: 1px;
    }

    /* Dashboard Cards */
    .invoice-dashboard .dashboard-card {
        background: var(--iv-surface);
        border: 1px solid var(--iv-border);
        border-radius: 12px;
        box-shadow: var(--iv-shadow-sm);
        transition: border-color .15s ease;
    }

    .invoice-dashboard .dashboard-card-header {
        border-bottom: 1px solid var(--iv-border);
        padding: 12px 18px;
        background: transparent;
    }

    .invoice-dashboard .dashboard-card-body {
        padding: 14px 18px;
    }

    .invoice-dashboard .card-kicker {
        font-size: .67rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--iv-muted);
    }

    .invoice-dashboard .card-heading {
        color: var(--iv-text-dark);
        font-size: .92rem;
        font-weight: 700;
        letter-spacing: -.01em;
    }

    /* Pipeline Transaksi (Snug, compact, zero downward dead space) */
    .invoice-dashboard .pipeline-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 10px;
    }

    .invoice-dashboard .pipeline-item {
        background: var(--iv-surface);
        border: 1px solid var(--status-border);
        border-radius: 11px;
        padding: 11px 12px 10px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: auto;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }

    .invoice-dashboard .pipeline-item:hover {
        transform: translateY(-2px);
        box-shadow: var(--iv-shadow-md);
    }

    .invoice-dashboard .pipeline-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--status-icon-bg);
        color: var(--status-accent);
        flex-shrink: 0;
    }

    .invoice-dashboard .pipeline-status-badge {
        background: var(--status-pill-bg);
        color: var(--status-pill-text);
        border: 1px solid var(--status-border);
        padding: 2.5px 6.5px;
        font-size: 10.5px;
        font-weight: 600;
        border-radius: 5px;
        letter-spacing: .02em;
        white-space: nowrap;
    }

    .invoice-dashboard .pipeline-count-pill {
        background: var(--status-bg);
        color: var(--status-pill-text);
        border: 1px solid var(--status-border);
        padding: 2px 6.5px;
        border-radius: 6px;
        font-size: 11px;
        white-space: nowrap;
        display: inline-flex;
        align-items: baseline;
        gap: 2.5px;
    }

    .invoice-dashboard .pipeline-count-num {
        font-weight: 700;
    }

    .invoice-dashboard .pipeline-count-unit {
        font-size: 9.5px;
        opacity: .75;
    }

    .invoice-dashboard .pipeline-item-body {
        margin-top: 9px;
        padding-top: 7px;
        border-top: 1px solid var(--status-border);
    }

    .invoice-dashboard .pipeline-amount-label {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--iv-muted);
        line-height: 1.2;
    }

    .invoice-dashboard .pipeline-amount-value {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--iv-text-dark);
        line-height: 1.35;
        overflow-wrap: anywhere;
        margin-top: 2px;
    }

    /* Soft pill badges */
    .invoice-dashboard .pill-soft-emerald {
        background-color: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
        border-radius: 999px;
        padding: 3.5px 10px;
        font-size: 11px;
        font-weight: 600;
    }

    .invoice-dashboard .pill-soft-rose {
        background-color: #fff1f2;
        color: #9f1239;
        border: 1px solid #fecdd3;
        border-radius: 999px;
        padding: 3.5px 10px;
        font-size: 11px;
        font-weight: 600;
    }

    .invoice-dashboard .pill-soft-slate {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 3.5px 10px;
        font-size: 11px;
        font-weight: 600;
    }

    /* Charts */
    .invoice-dashboard .apex-charts {
        min-height: 236px;
    }

    /* Clean Table */
    .invoice-dashboard .clean-table thead th {
        border-top: 0;
        border-bottom: 1px solid var(--iv-border);
        color: #64748b;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        background: #f8fafc;
        padding: 9px 14px;
    }

    .invoice-dashboard .clean-table tbody td {
        border-color: var(--iv-border);
        padding: 9px 14px;
        vertical-align: middle;
        font-size: 12.5px;
    }

    .invoice-dashboard .clean-table tbody tr:hover td {
        background: #fcfdfe;
    }

    .invoice-dashboard .btn-detail-action {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        background: #fff5f5;
        color: #e21a1a;
        border: 1px solid #fecaca;
        font-size: 11.5px;
        font-weight: 600;
        border-radius: 6px;
        padding: 3px 9px;
        text-decoration: none;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }

    .invoice-dashboard .btn-detail-action:hover {
        background: #fee2e2;
        color: #b91c1c;
        border-color: #fca5a5;
    }

    .invoice-dashboard .btn-view-all {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #e21a1a;
        color: #ffffff;
        border: 0;
        font-size: 12px;
        font-weight: 600;
        border-radius: 7px;
        padding: 4.5px 10px;
        text-decoration: none;
        transition: background-color .15s ease;
    }

    .invoice-dashboard .btn-view-all:hover {
        background: #b91c1c;
        color: #ffffff;
    }

    /* Responsive Breakpoints */
    @media (max-width: 1199.98px) {
        .invoice-dashboard .pipeline-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .invoice-dashboard .pipeline-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .invoice-dashboard .hero-metric-tile {
            min-width: 100%;
        }
    }

    @media (max-width: 480px) {
        .invoice-dashboard .pipeline-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="invoice-dashboard">
    {{-- 1. HERO SECTION --}}
    <div class="dashboard-hero mb-3">
        <div class="row align-items-center g-3">
            <div class="col-xl-9 col-lg-7">
                <h2 class="hero-heading fw-bold mb-1.5">Invoice Verification Analytics</h2>
                <p class="hero-lead text-muted mb-0">Ringkasan performa berkas, verifikasi administrasi akuntansi, dan realisasi pembayaran tagihan secara terpadu.</p>
            </div>
            <div class="col-xl-3 col-lg-3">
                <div class="d-flex flex-wrap align-items-stretch gap-2.5 justify-content-lg-end">
                    <div class="hero-metric-tile">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="hero-metric-icon status-submitted">
                                <iconify-icon icon="solar:document-text-outline" class="fs-18"></iconify-icon>
                            </span>
                            <span class="hero-metric-title">Total Transaksi</span>
                        </div>
                        <div class="hero-metric-val">{{ $summary['transactions_total'] }} <span class="hero-metric-unit">berkas</span></div>
                        <div class="hero-metric-sub">Seluruh transaksi aktif</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. PIPELINE TRANSAKSI (Compact, modern cards, zero downward dead space) --}}
    <div class="dashboard-card mb-3">
        <div class="dashboard-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="d-flex align-items-center gap-1.5 card-kicker mb-1">
                    <iconify-icon icon="solar:sort-horizontal-outline" class="fs-14"></iconify-icon>
                    <span>Pipeline Transaksi</span>
                </div>
                <h5 class="card-heading mb-0">Alur Status Dokumen</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="pill-soft-slate">
                    <iconify-icon icon="solar:wallet-outline" class="align-middle me-1"></iconify-icon>
                    {{ $formatRupiah($pipelineTotalAmount) }}
                </span>
                <span class="pill-soft-emerald">
                    <iconify-icon icon="solar:check-circle-outline" class="align-middle me-1"></iconify-icon>
                    {{ $analytics['insights']['completion_rate'] }}% Selesai
                </span>
            </div>
        </div>
        <div class="dashboard-card-body">
            <div class="pipeline-grid">
                @foreach ($pipelineMetrics as $metric)
                    @php
                        $statusClass = match ($metric['status']) {
                            'DRAFT' => 'status-draft',
                            'SUBMITTED' => 'status-submitted',
                            'IN_REVIEW' => 'status-in_review',
                            'NOT_APPROVED' => 'status-not_approved',
                            'RECEIVED' => 'status-received',
                            'COMPLETED' => 'status-completed',
                            default => 'status-draft',
                        };
                    @endphp
                    <div class="pipeline-item {{ $statusClass }}">
                        <div class="d-flex align-items-center justify-content-between gap-1.5">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <span class="pipeline-icon">
                                    <iconify-icon icon="{{ $metric['icon'] }}" class="fs-16"></iconify-icon>
                                </span>
                                <span class="pipeline-status-badge">
                                    {{ $metric['label'] }}
                                </span>
                            </div>
                            <div class="pipeline-count-pill">
                                <span class="pipeline-count-num">{{ $metric['count'] }}</span>
                                <span class="pipeline-count-unit">trx</span>
                            </div>
                        </div>
                        <div class="pipeline-item-body">
                            <div class="pipeline-amount-label">Nominal Berkas</div>
                            <div class="pipeline-amount-value">{{ $formatRupiah($metric['amount']) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 3. ANALYTICS ROW 1: Trend Transaksi & Distribusi Status --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="card-kicker mb-1">Trend Transaksi</div>
                        <h5 class="card-heading mb-0">Volume 6 Bulan Terakhir</h5>
                    </div>
                    @if ($periodChange >= 0)
                        <span class="pill-soft-emerald">
                            +{{ $periodChange }}% vs periode lalu
                        </span>
                    @else
                        <span class="pill-soft-rose">
                            {{ $periodChange }}% vs periode lalu
                        </span>
                    @endif
                </div>
                <div class="dashboard-card-body">
                    <div id="iv-transaction-trend" class="apex-charts"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-header">
                    <div class="card-kicker mb-1">Distribusi Status</div>
                    <h5 class="card-heading mb-0">Komposisi Berkas Aktif</h5>
                </div>
                <div class="dashboard-card-body">
                    <div id="iv-status-donut" class="apex-charts"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. ANALYTICS ROW 2: Transaksi Terbaru & Nominal Tahunan (Equal height parity) --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="card-kicker mb-1">Aktivitas Terkini</div>
                        <h5 class="card-heading mb-0">Transaksi Terbaru</h5>
                    </div>
                    <a href="{{ route('invoice-verification.transactions.index') }}" class="btn-view-all">
                        Lihat Semua
                        <iconify-icon icon="solar:arrow-right-outline" class="fs-14 align-middle"></iconify-icon>
                    </a>
                </div>
                <div class="dashboard-card-body p-0">
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
                                            <div class="fw-semibold text-dark">{{ $transaction->registration_number }}</div>
                                            <div class="text-muted small text-truncate" style="max-width: 270px;">{{ $transaction->title }}</div>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $transaction->transactionType?->name ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-inline-block text-dark" style="max-width: 180px;">{{ $transaction->vendor?->name ?? '-' }}</span>
                                        </td>
                                        <td>
                                            @include('invoice-verification.components.status-badge', ['value' => $transaction->status])
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn-detail-action">
                                                Detail
                                                <iconify-icon icon="solar:arrow-right-outline" class="fs-12"></iconify-icon>
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
            <div class="dashboard-card h-100">
                <div class="dashboard-card-header">
                    <div class="card-kicker mb-1">Nominal Tahunan</div>
                    <h5 class="card-heading mb-0">Akumulasi 5 Tahun</h5>
                </div>
                <div class="dashboard-card-body">
                    <div id="iv-amount-yearly" class="apex-charts"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- 5. ANALYTICS ROW 3: Nominal Mingguan & Bulanan --}}
    <div class="row g-3">
        <div class="col-xl-6">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-header">
                    <div class="card-kicker mb-1">Nominal Mingguan</div>
                    <h5 class="card-heading mb-0">Nilai Transaksi 8 Minggu Terakhir</h5>
                </div>
                <div class="dashboard-card-body">
                    <div id="iv-amount-weekly" class="apex-charts"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-header">
                    <div class="card-kicker mb-1">Nominal Bulanan</div>
                    <h5 class="card-heading mb-0">Nilai Transaksi 12 Bulan Terakhir</h5>
                </div>
                <div class="dashboard-card-body">
                    <div id="iv-amount-monthly" class="apex-charts"></div>
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
