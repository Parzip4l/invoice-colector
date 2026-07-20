@extends('layouts.vertical', ['subtitle' => 'Approval Queue'])

@section('css')
@include('invoice-verification.partials.table-ui')
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::KEPALA_DEPARTEMEN) ? 'Kadep Review' : 'Kadiv Review', 'subtitle' => 'Approval Transaksi'])
@include('invoice-verification.partials.flash')

<div class="card iv-table-card">
    <div class="card-header bg-white border-bottom">
        <form method="GET" class="iv-table-toolbar" style="--iv-filter-count: 1;">
            <div>
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><iconify-icon icon="solar:magnifer-outline" class="fs-18"></iconify-icon></span>
                    <input type="search" class="form-control" name="search" value="{{ $search }}" placeholder="Transaksi, tahap, catatan">
                </div>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach (\App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus::cases() as $approvalStatus)
                        <option value="{{ $approvalStatus->value }}" @selected($status === $approvalStatus->value)>{{ str($approvalStatus->value)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1"><iconify-icon icon="solar:filter-outline" class="fs-18"></iconify-icon><span>Filter</span></button>
            <a href="{{ route('invoice-verification.approvals.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1"><iconify-icon icon="solar:restart-outline" class="fs-18"></iconify-icon><span>Reset</span></a>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 iv-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.approvals.index', 'column' => 'transaction', 'label' => 'Transaksi'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.approvals.index', 'column' => 'step', 'label' => 'Tahap'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.approvals.index', 'column' => 'status', 'label' => 'Status'])</th>
                        <th>Catatan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($approvalTransactions as $approvalTransaction)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $approvalTransaction->transaction?->registration_number }}</div>
                                <div class="text-muted small text-truncate iv-cell-truncate" title="{{ $approvalTransaction->transaction?->title }}">{{ $approvalTransaction->transaction?->title }}</div>
                            </td>
                            <td>{{ $approvalTransaction->approvalFlow?->step_name }}</td>
                            <td>@include('invoice-verification.components.status-badge', ['value' => $approvalTransaction->status])</td>
                            <td><div class="text-truncate iv-cell-truncate" title="{{ $approvalTransaction->notes ?? '-' }}">{{ $approvalTransaction->notes ?? '-' }}</div></td>
                            <td class="text-end">
                                <div class="iv-actions">
                                    <a href="{{ route('invoice-verification.transactions.show', $approvalTransaction->transaction) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                    @if ($approvalTransaction->status->value === 'PENDING')
                                        <form method="POST" action="{{ route('invoice-verification.approvals.update', $approvalTransaction) }}" class="d-inline-flex">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="APPROVED">
                                            <button class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('invoice-verification.approvals.update', $approvalTransaction) }}" class="d-inline-flex">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="REJECTED">
                                            <input type="hidden" name="notes" value="Dikembalikan untuk revisi dokumen awal.">
                                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Tidak ada approval yang ditugaskan ke Anda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-top">
            {{ $approvalTransactions->links() }}
        </div>
    </div>
</div>
@endsection
