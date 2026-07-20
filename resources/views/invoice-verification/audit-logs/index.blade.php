@extends('layouts.vertical', ['subtitle' => 'Audit Logs'])

@section('css')
@include('invoice-verification.partials.table-ui')
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Audit Logs', 'subtitle' => 'Tracking'])
@include('invoice-verification.partials.flash')

<div class="card iv-table-card">
    <div class="card-header bg-white border-bottom">
        <form method="GET" class="iv-table-toolbar" style="--iv-filter-count: 3;">
            <div>
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><iconify-icon icon="solar:magnifer-outline" class="fs-18"></iconify-icon></span>
                    <input type="search" class="form-control" name="search" value="{{ $search }}" placeholder="Module, action, reference, transaction">
                </div>
            </div>
            <div>
                <label class="form-label">Module</label>
                <input type="text" class="form-control" name="module" value="{{ $module }}" placeholder="module">
            </div>
            <div>
                <label class="form-label">Action</label>
                <input type="text" class="form-control" name="action" value="{{ $action }}" placeholder="action">
            </div>
            <div>
                <label class="form-label">Transaction ID</label>
                <input type="text" class="form-control" name="transaction_id" value="{{ $transactionId }}" placeholder="transaction id">
            </div>
            <button class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1"><iconify-icon icon="solar:filter-outline" class="fs-18"></iconify-icon><span>Filter</span></button>
            <a href="{{ route('invoice-verification.audit-logs.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1"><iconify-icon icon="solar:restart-outline" class="fs-18"></iconify-icon><span>Reset</span></a>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 iv-table" style="--iv-table-min-width: 1100px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.audit-logs.index', 'column' => 'created_at', 'label' => 'Created At'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.audit-logs.index', 'column' => 'module', 'label' => 'Module'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.audit-logs.index', 'column' => 'action', 'label' => 'Action'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.audit-logs.index', 'column' => 'transaction_id', 'label' => 'Transaction'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.audit-logs.index', 'column' => 'reference_type', 'label' => 'Reference'])</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditLogs as $auditLog)
                        <tr>
                            <td class="ps-4">{{ $auditLog->created_at?->format('d M Y H:i:s') }}</td>
                            <td>{{ $auditLog->module }}</td>
                            <td>{{ $auditLog->action }}</td>
                            <td>{{ $auditLog->transaction_id ?? '-' }}</td>
                            <td><div class="text-truncate iv-cell-truncate" style="--iv-cell-width: 280px;" title="{{ $auditLog->reference_type ? class_basename($auditLog->reference_type) : '-' }} {{ $auditLog->reference_id ?? '' }}">{{ $auditLog->reference_type ? class_basename($auditLog->reference_type) : '-' }} {{ $auditLog->reference_id ?? '' }}</div></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada audit log.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-top">
            {{ $auditLogs->links() }}
        </div>
    </div>
</div>
@endsection
