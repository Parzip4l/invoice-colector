@extends('layouts.vertical', ['subtitle' => 'Dashboard'])

@section('content')
@php
    $periodChange = $analytics['insights']['period_change'];
    $periodTone = $periodChange >= 0 ? 'success' : 'danger';
    $summaryCards = [
        [
            'label' => 'Total Transaksi',
            'value' => $summary['transactions_total'],
            'icon' => 'solar:bill-list-outline',
            'tone' => 'primary',
            'meta' => ($periodChange >= 0 ? '+' : '') . $periodChange . '% vs periode lalu',
        ],
        [
            'label' => 'Total Pending',
            'value' => $analytics['insights']['total_pending'],
            'icon' => 'solar:hourglass-line-outline',
            'tone' => 'warning',
            'meta' => $summary['transactions_submitted'] . ' submitted, ' . $summary['transactions_in_review'] . ' in review',
        ],
        [
            'label' => 'Total Nominal',
            'value' => 'Rp ' . number_format((float) $analytics['insights']['nominal_total'], 0, ',', '.'),
            'icon' => 'solar:wallet-money-outline',
            'tone' => 'info',
            'meta' => 'Akumulasi nilai transaksi',
        ],
        [
            'label' => 'Completion Rate',
            'value' => $analytics['insights']['completion_rate'] . '%',
            'icon' => 'solar:chart-2-outline',
            'tone' => 'success',
            'meta' => $summary['completed_transactions'] . ' transaksi selesai',
        ],
    ];
@endphp

<style>
    .invoice-dashboard {
        --iv-surface: #ffffff;
        --iv-soft: #f6f8fb;
        --iv-border: rgba(33, 37, 41, .075);
        --iv-shadow: 0 12px 32px rgba(27, 36, 54, .08);
        color: #1f2937;
    }

    .invoice-dashboard .dashboard-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(226, 26, 26, .14);
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(226, 26, 26, .10), rgba(192, 127, 32, .10) 52%, rgba(22, 163, 74, .07));
        box-shadow: var(--iv-shadow);
    }

    .invoice-dashboard .dashboard-hero::after {
        content: "";
        position: absolute;
        inset: auto -80px -120px auto;
        width: 280px;
        height: 280px;
        background: radial-gradient(circle, rgba(226, 26, 26, .18), transparent 68%);
        pointer-events: none;
    }

    .invoice-dashboard .hero-kicker,
    .invoice-dashboard .metric-label,
    .invoice-dashboard .chart-kicker {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .invoice-dashboard .metric-card,
    .invoice-dashboard .analytics-card,
    .invoice-dashboard .queue-panel,
    .invoice-dashboard .table-panel {
        border: 1px solid var(--iv-border);
        border-radius: 16px;
        background: var(--iv-surface);
        box-shadow: var(--iv-shadow);
    }

    .invoice-dashboard .metric-card {
        min-height: 154px;
    }

    .invoice-dashboard .metric-icon {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
    }

    .invoice-dashboard .analytics-card .card-header,
    .invoice-dashboard .queue-panel .card-header,
    .invoice-dashboard .table-panel .card-header {
        border-bottom: 1px solid var(--iv-border);
        background: transparent;
        padding: 18px 20px 12px;
    }

    .invoice-dashboard .analytics-card .card-body,
    .invoice-dashboard .queue-panel .card-body,
    .invoice-dashboard .table-panel .card-body {
        padding: 18px 20px 20px;
    }

    .invoice-dashboard .apex-charts {
        min-height: 260px;
    }

    .invoice-dashboard .stat-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .invoice-dashboard .stat-pill {
        border: 1px solid var(--iv-border);
        border-radius: 14px;
        background: rgba(255, 255, 255, .68);
        padding: 14px 16px;
    }

    .invoice-dashboard .clean-table thead th {
        border-top: 0;
        border-bottom: 1px solid var(--iv-border);
        color: #6b7280;
        font-size: .74rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        background: #f9fafb;
    }

    .invoice-dashboard .clean-table tbody td {
        border-color: rgba(33, 37, 41, .055);
        padding-top: 14px;
        padding-bottom: 14px;
        vertical-align: middle;
    }

    .invoice-dashboard .queue-item {
        border: 1px solid var(--iv-border);
        border-radius: 14px;
        background: linear-gradient(180deg, #fff, #fbfcfe);
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
        .invoice-dashboard .stat-strip {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .invoice-dashboard .stat-strip {
            grid-template-columns: 1fr;
        }

        .invoice-dashboard .dashboard-hero,
        .invoice-dashboard .metric-card,
        .invoice-dashboard .analytics-card,
        .invoice-dashboard .queue-panel,
        .invoice-dashboard .table-panel {
            border-radius: 14px;
        }
    }
</style>

<div class="invoice-dashboard">
    <div class="dashboard-hero p-4 p-xl-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-xl-7">
                <span class="hero-kicker text-primary">Sistem Verifikasi</span>
                <h2 class="mt-2 mb-2 fw-bold">Invoice Verification Analytics</h2>
                <p class="text-muted mb-0">Ringkasan performa transaksi, nominal pembayaran, dan progress operasional dalam satu dashboard.</p>
            </div>
            <div class="col-xl-5">
                <div class="stat-strip">
                    <div class="stat-pill">
                        <div class="text-muted small">Finance Queue</div>
                        <div class="h4 mb-0">{{ $summary['transactions_finance_queue'] }}</div>
                    </div>
                    <div class="stat-pill">
                        <div class="text-muted small">Review Vendor</div>
                        <div class="h4 mb-0">{{ $summary['documents_pending_review'] }}</div>
                    </div>
                    <div class="stat-pill">
                        <div class="text-muted small">Arsip Final</div>
                        <div class="h4 mb-0">{{ $summary['compiled_documents'] }}</div>
                    </div>
                    <div class="stat-pill">
                        <div class="text-muted small">Audit Hari Ini</div>
                        <div class="h4 mb-0">{{ $summary['audit_entries_today'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($summaryCards as $card)
            <div class="col-md-6 col-xl-3">
                <div class="card metric-card h-100 mb-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="metric-label text-{{ $card['tone'] }}">{{ $card['label'] }}</div>
                                <h2 class="fw-bold mt-2 mb-1">{{ $card['value'] }}</h2>
                                <div class="text-muted small">{{ $card['meta'] }}</div>
                            </div>
                            <span class="metric-icon bg-{{ $card['tone'] }}-subtle text-{{ $card['tone'] }}">
                                <iconify-icon icon="{{ $card['icon'] }}" class="fs-26"></iconify-icon>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
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
                    <p class="text-muted mb-0">Status real-time dengan fallback data demo.</p>
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
