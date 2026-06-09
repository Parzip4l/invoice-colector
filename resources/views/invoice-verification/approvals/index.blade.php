@extends('layouts.vertical', ['subtitle' => 'Approval Queue'])

@section('content')
@include('layouts.partials.page-title', ['title' => auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::KEPALA_DEPARTEMEN) ? 'Kadep Review' : 'Kadiv Review', 'subtitle' => 'Approval Transaksi'])
@include('invoice-verification.partials.flash')

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transaksi</th>
                        <th>Tahap</th>
                        <th>Status</th>
                        <th>Catatan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($approvalTransactions as $approvalTransaction)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $approvalTransaction->transaction?->registration_number }}</div>
                                <div class="text-muted small">{{ $approvalTransaction->transaction?->title }}</div>
                                <a href="{{ route('invoice-verification.transactions.show', $approvalTransaction->transaction) }}" class="btn btn-sm btn-link px-0">Lihat Detail & Dokumen Vendor</a>
                            </td>
                            <td>{{ $approvalTransaction->approvalFlow?->step_name }}</td>
                            <td>@include('invoice-verification.components.status-badge', ['value' => $approvalTransaction->status])</td>
                            <td>{{ $approvalTransaction->notes ?? '-' }}</td>
                            <td class="text-end">
                                @if ($approvalTransaction->status->value === 'PENDING')
                                    <form method="POST" action="{{ route('invoice-verification.approvals.update', $approvalTransaction) }}" class="d-inline-flex gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="APPROVED">
                                        <button class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('invoice-verification.approvals.update', $approvalTransaction) }}" class="d-inline-flex gap-2 ms-1">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="REJECTED">
                                        <input type="hidden" name="notes" value="Dikembalikan untuk revisi dokumen awal.">
                                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                @endif
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

        <div class="mt-3">
            {{ $approvalTransactions->links() }}
        </div>
    </div>
</div>
@endsection
