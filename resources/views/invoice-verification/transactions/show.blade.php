@extends('layouts.vertical', ['subtitle' => 'Transaction Detail'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Transactions', 'subtitle' => 'Transaction Detail'])
@include('invoice-verification.partials.flash')

@php
    $isVendorRevisionView = auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::VENDOR)
        && $transaction->status?->value === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::REVISION_IN_PROGRESS->value;
@endphp

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <div>
                        <h4 class="mb-1">{{ $transaction->title }}</h4>
                        <p class="text-muted mb-2">{{ $transaction->registration_number }} · {{ $transaction->transactionType?->name }}</p>
                        <div class="d-flex flex-wrap gap-2">
                            @include('invoice-verification.components.status-badge', ['value' => $transaction->status])
                            <span class="badge bg-light text-dark">{{ $transaction->current_step?->label() }}</span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        @can('uploadDocuments', $transaction)
                            <a href="{{ route('invoice-verification.transactions.documents.show', $transaction) }}" class="btn btn-outline-primary">Upload Dokumen</a>
                        @endcan
                        @if (
                            auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::ADMIN_DIVISI)
                            && $transaction->status?->value === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ADMIN_GENERATE_DOCUMENTS->value
                        )
                            <form method="POST" action="{{ route('invoice-verification.transactions.admin-documents.generate', $transaction) }}">
                                @csrf
                                <button class="btn btn-primary">Generate Lembar PPA & Verifikasi</button>
                            </form>
                        @endif
                        @if ($transaction->isPpa() && ! auth()->user()?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::VENDOR))
                            <span class="btn btn-outline-secondary disabled">Upload PPA Dilakukan Vendor</span>
                        @endif
                        @if ($transaction->isPpa() && $transaction->ppaVerificationSheet)
                            <a href="{{ route('invoice-verification.transactions.ppa-verification-sheets.edit', $transaction) }}" class="btn btn-outline-secondary">Lembar Verifikasi PPA</a>
                        @endif
                        @can('verifyAccounting', $transaction)
                            <a href="{{ route('invoice-verification.transactions.accounting-verifications.edit', $transaction) }}" class="btn btn-primary">Verifikasi Akuntansi</a>
                        @endcan
                        @can('processFinance', $transaction)
                            @if ($transaction->status?->value === 'FINANCE_PROCESS')
                                <a href="{{ route('invoice-verification.finance.index') }}" class="btn btn-primary">Proses Finance</a>
                            @endif
                        @endcan
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Vendor</small>
                            <div class="fw-semibold">{{ $transaction->vendor?->name ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Divisi / Departemen</small>
                            <div class="fw-semibold">{{ $transaction->division?->name }}</div>
                            <div class="text-muted small">{{ $transaction->department?->name }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Memo / Kontrak</small>
                            <div class="fw-semibold">{{ $transaction->memoRequest?->memo_number ?? $transaction->invoiceMetadata?->memo_number ?? '-' }}</div>
                            <div class="text-muted small">{{ $transaction->agreementReference?->contract_number ?? $transaction->contract_number ?? $transaction->invoiceMetadata?->contract_number ?? '-' }}</div>
                            @if ($transaction->memoRequest?->file_path)
                                <a href="{{ route('invoice-verification.master-data.memo-requests.download', $transaction->memoRequest) }}" class="btn btn-sm btn-link px-0 mt-2">Lihat File Memo</a>
                            @endif
                            @if ($transaction->agreementReference?->file_path)
                                <a href="{{ route('invoice-verification.master-data.agreement-references.download', $transaction->agreementReference) }}" class="btn btn-sm btn-link px-0 d-block">Lihat File Kontrak</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Invoice Metadata</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('invoice-verification.transactions.invoice-metadata.update', $transaction) }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-12">
                        <label class="form-label">Nomor Invoice</label>
                        <input type="text" class="form-control" name="invoice_number" value="{{ old('invoice_number', $transaction->invoiceMetadata?->invoice_number) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tanggal Invoice</label>
                        <input type="date" class="form-control" name="invoice_date" value="{{ old('invoice_date', optional($transaction->invoiceMetadata?->invoice_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Bank</label>
                        <input type="text" class="form-control" name="bank_name" value="{{ old('bank_name', $transaction->invoiceMetadata?->bank_name) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nilai Invoice</label>
                        <input type="number" class="form-control" step="0.01" name="invoice_value" value="{{ old('invoice_value', $transaction->invoiceMetadata?->invoice_value) }}">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-outline-primary w-100">Perbarui Metadata</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="{{ $isVendorRevisionView ? 'col-12' : 'col-xl-8' }}">
        @unless($isVendorRevisionView)
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Generated Initial Documents</h5>
                    <span class="text-muted small">Dokumen yang dikontrol sistem dan approval terkait.</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dokumen</th>
                                    <th>Nomor</th>
                                    <th>Status Generate</th>
                                    <th>Approval Mode</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transaction->generatedDocuments as $generatedDocument)
                                    <tr>
                                        <td>{{ str($generatedDocument->document_code)->replace('_', ' ')->title() }}</td>
                                        <td>{{ $generatedDocument->document_number ?? '-' }}</td>
                                        <td>{{ $generatedDocument->generation_status->value }}</td>
                                        <td>{{ $generatedDocument->approval_mode->value }}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <a href="{{ route('invoice-verification.generated-documents.preview', $generatedDocument) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Preview File</a>
                                                <a href="{{ route('invoice-verification.generated-documents.show', $generatedDocument) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada generated document.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($transaction->ppaVerificationSheet?->file_path)
                        <div class="border rounded-3 p-3 mt-3 d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <div class="fw-semibold">Lembar Checklist PPA</div>
                                <div class="text-muted small">{{ $transaction->ppaVerificationSheet->file_name ?? 'Checklist PPA' }}</div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="{{ route('invoice-verification.transactions.ppa-verification-sheets.preview', $transaction) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Preview File</a>
                                <a href="{{ route('invoice-verification.transactions.ppa-verification-sheets.edit', $transaction) }}" class="btn btn-sm btn-outline-secondary">Detail Checklist</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endunless

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Dokumen Transaksi</h5>
            </div>
            <div class="card-body">
                @include('invoice-verification.components.document-table', ['documents' => $transaction->latestDocuments])
            </div>
        </div>

        @if ($mismatches && ! $isVendorRevisionView)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Mismatch Checklist PPA</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dokumen</th>
                                    <th>Checklist</th>
                                    <th>File Aktual</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($mismatches as $item)
                                    <tr>
                                        <td>{{ $item['document_name'] }}</td>
                                        <td>{{ $item['checklist_status'] }}</td>
                                        <td>{{ $item['actual_available'] ? 'AVAILABLE' : 'MISSING' }}</td>
                                        <td>
                                            <span class="badge {{ $item['is_mismatch'] ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">
                                                {{ $item['is_mismatch'] ? 'Mismatch' : 'Match' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @unless($isVendorRevisionView)
        <div class="col-xl-4">
            @include('invoice-verification.components.timeline', ['histories' => $transaction->statusHistory])

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Audit Trail</h5>
                </div>
                <div class="card-body">
                    @forelse ($auditLogs as $log)
                        <div class="{{ !$loop->last ? 'pb-3 mb-3 border-bottom' : '' }}">
                            <div class="fw-semibold">{{ str($log->action)->replace('_', ' ')->title() }}</div>
                            <div class="text-muted small">{{ $log->module }}</div>
                            <small class="text-muted">{{ $log->created_at?->format('d M Y H:i') }}</small>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada audit log.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endunless
</div>
@endsection
