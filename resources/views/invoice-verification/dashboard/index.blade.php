@extends('layouts.vertical', ['subtitle' => 'Dashboard'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Invoice Verification', 'subtitle' => 'Dashboard'])
@include('invoice-verification.partials.flash')

<div class="row">
    @foreach ([
        ['label' => 'Total Transaksi', 'value' => $summary['transactions_total'], 'icon' => 'solar:bill-list-outline'],
        ['label' => 'Menunggu Approval', 'value' => $summary['transactions_waiting_approval'], 'icon' => 'solar:checklist-minimalistic-outline'],
        ['label' => 'Dokumen Review Vendor', 'value' => $summary['documents_pending_review'], 'icon' => 'solar:file-warning-outline'],
        ['label' => 'Antrean Finance', 'value' => $summary['transactions_finance_queue'], 'icon' => 'solar:wallet-money-outline'],
        ['label' => 'Arsip Final', 'value' => $summary['compiled_documents'], 'icon' => 'solar:archive-outline'],
    ] as $card)
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">{{ $card['label'] }}</p>
                            <h3 class="mb-0">{{ $card['value'] }}</h3>
                        </div>
                        <div class="avatar-md bg-primary bg-opacity-10 rounded-circle">
                            <iconify-icon icon="{{ $card['icon'] }}" class="fs-32 text-primary avatar-title"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">Transaksi Terbaru</h5>
                    <p class="text-muted mb-0">Pantau transaksi lintas PPA, SPU, SPUK, dan Kas Kecil.</p>
                </div>
                <a href="{{ route('invoice-verification.transactions.index') }}" class="btn btn-sm btn-primary">Lihat Semua</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Registrasi</th>
                                <th>Jenis</th>
                                <th>Vendor</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentTransactions as $transaction)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $transaction->registration_number }}</div>
                                        <div class="text-muted small">{{ $transaction->title }}</div>
                                    </td>
                                    <td>{{ $transaction->transactionType?->name }}</td>
                                    <td>{{ $transaction->vendor?->name ?? '-' }}</td>
                                    <td>@include('invoice-verification.components.status-badge', ['value' => $transaction->status])</td>
                                    <td class="text-end">
                                        <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">Detail</a>
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
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-1">Approval Queue Terkini</h5>
                <p class="text-muted mb-0">Dokumen awal dan lembar verifikasi yang sedang menunggu approver sebelum masuk scan, upload, dan finance.</p>
            </div>
            <div class="card-body">
                @forelse ($approvalQueue as $approval)
                    <div class="border rounded-3 p-3 {{ !$loop->last ? 'mb-3' : '' }}">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <div class="fw-semibold">{{ $approval->transaction?->registration_number }}</div>
                                <p class="text-muted mb-1">{{ $approval->approvalFlow?->step_name }}</p>
                            </div>
                            @include('invoice-verification.components.status-badge', ['value' => $approval->status])
                        </div>
                        <small class="text-muted">{{ $approval->transaction?->title }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0">Tidak ada antrean approval saat ini.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
