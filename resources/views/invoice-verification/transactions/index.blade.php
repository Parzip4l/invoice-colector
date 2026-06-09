@extends('layouts.vertical', ['subtitle' => 'Transactions'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Invoice Verification', 'subtitle' => 'Transactions'])
@include('invoice-verification.partials.flash')

@php
    $isRevisionList = request('status') === 'REVISION_IN_PROGRESS';
@endphp

<div class="card">
    <div class="card-header">
        @if ($isRevisionList)
            <div class="alert alert-warning border-0 mb-3">
                <div class="fw-semibold">Daftar Revisi</div>
                <div>Transaksi di bawah ini memiliki dokumen yang perlu diperbaiki atau diupload ulang oleh Vendor.</div>
            </div>
        @endif
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Cari</label>
                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="registrasi, vendor, judul">
            </div>
            <div class="col-md-3">
                <label class="form-label">Jenis Transaksi</label>
                <select name="transaction_type_id" class="form-select">
                    <option value="">Semua</option>
                    @foreach ($transactionTypes as $type)
                        <option value="{{ $type->id }}" @selected(request('transaction_type_id') === $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <input type="text" class="form-control" name="status" value="{{ request('status') }}" placeholder="WAITING_APPROVAL">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100">Filter</button>
                @can('create', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class)
                    <a href="{{ route('invoice-verification.transactions.create') }}" class="btn btn-success w-100">Buat</a>
                @endcan
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Registrasi</th>
                        <th>Jenis</th>
                        <th>Vendor</th>
                        <th>Invoice</th>
                        <th>Status</th>
                        <th>Step</th>
                        <th>Dibuat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $transaction->registration_number }}</div>
                                <div class="text-muted small">{{ $transaction->title }}</div>
                            </td>
                            <td>{{ $transaction->transactionType?->code?->value ?? '-' }}</td>
                            <td>{{ $transaction->vendor?->name ?? '-' }}</td>
                            <td>{{ $transaction->invoiceMetadata?->invoice_number ?? '-' }}</td>
                            <td>@include('invoice-verification.components.status-badge', ['value' => $transaction->status])</td>
                            <td>{{ $transaction->current_step?->label() }}</td>
                            <td>{{ $transaction->created_at?->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                @if (in_array($transaction->status?->value, ['DRAFT', 'VENDOR_INPUT'], true))
                                    @can('uploadDocuments', $transaction)
                                        <a href="{{ route('invoice-verification.transactions.documents.show', $transaction) }}" class="btn btn-sm btn-primary">Upload Dokumen</a>
                                    @endcan
                                @endif
                                @if ($transaction->status?->value === 'REVISION_IN_PROGRESS')
                                    @can('uploadDocuments', $transaction)
                                        <a href="{{ route('invoice-verification.transactions.documents.show', $transaction) }}" class="btn btn-sm btn-warning">Upload Ulang</a>
                                    @endcan
                                @endif
                                <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
