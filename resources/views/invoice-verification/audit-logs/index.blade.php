@extends('layouts.vertical', ['subtitle' => 'Audit Logs'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Audit Logs', 'subtitle' => 'Tracking'])
@include('invoice-verification.partials.flash')

<div class="card">
    <div class="card-header">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="module" value="{{ request('module') }}" placeholder="Filter module">
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" name="action" value="{{ request('action') }}" placeholder="Filter action">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="transaction_id" value="{{ request('transaction_id') }}" placeholder="Transaction ID">
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100">Go</button>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Created At</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Transaction</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditLogs as $auditLog)
                        <tr>
                            <td>{{ $auditLog->created_at?->format('d M Y H:i:s') }}</td>
                            <td>{{ $auditLog->module }}</td>
                            <td>{{ $auditLog->action }}</td>
                            <td>{{ $auditLog->transaction_id ?? '-' }}</td>
                            <td>{{ $auditLog->reference_type ? class_basename($auditLog->reference_type) : '-' }} {{ $auditLog->reference_id ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada audit log.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $auditLogs->links() }}
        </div>
    </div>
</div>
@endsection
