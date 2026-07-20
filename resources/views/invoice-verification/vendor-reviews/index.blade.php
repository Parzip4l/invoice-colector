@extends('layouts.vertical', ['subtitle' => 'Admin Review'])

@section('css')
@include('invoice-verification.partials.table-ui')
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Admin Review', 'subtitle' => 'Pengecekan Tagihan Vendor'])
@include('invoice-verification.partials.flash')

<div class="card iv-table-card">
    <div class="card-header bg-white border-bottom">
        <form method="GET" class="iv-table-toolbar" style="--iv-filter-count: 0;">
            <div>
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><iconify-icon icon="solar:magnifer-outline" class="fs-18"></iconify-icon></span>
                    <input type="search" class="form-control" name="search" value="{{ $search }}" placeholder="Transaksi, vendor, dokumen">
                </div>
            </div>
            <button class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1"><iconify-icon icon="solar:filter-outline" class="fs-18"></iconify-icon><span>Filter</span></button>
            <a href="{{ route('invoice-verification.vendor-reviews.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1"><iconify-icon icon="solar:restart-outline" class="fs-18"></iconify-icon><span>Reset</span></a>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 iv-table" style="--iv-table-min-width: 1200px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.vendor-reviews.index', 'column' => 'transaction', 'label' => 'Transaksi'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.vendor-reviews.index', 'column' => 'document', 'label' => 'Dokumen'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.vendor-reviews.index', 'column' => 'vendor', 'label' => 'Vendor'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.vendor-reviews.index', 'column' => 'version', 'label' => 'Versi'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.vendor-reviews.index', 'column' => 'uploaded_at', 'label' => 'Uploaded'])</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendingDocuments as $document)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $document->transaction?->registration_number }}</div>
                                <div class="text-muted small text-truncate iv-cell-truncate" title="{{ $document->transaction?->title }}">{{ $document->transaction?->title }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-truncate iv-cell-truncate" style="--iv-cell-width: 220px;" title="{{ $document->document_label ?: $document->documentType?->name }}">{{ $document->document_label ?: $document->documentType?->name }}</div>
                                @if ($document->document_information_json)
                                    <div class="text-muted small">
                                        {{ $document->document_information_json['document_number'] ?? '-' }}
                                        @if (!empty($document->document_information_json['document_date']))
                                            · {{ \Illuminate\Support\Carbon::parse($document->document_information_json['document_date'])->format('d M Y') }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td><div class="text-truncate iv-cell-truncate" style="--iv-cell-width: 190px;" title="{{ $document->transaction?->vendor?->name ?? '-' }}">{{ $document->transaction?->vendor?->name ?? '-' }}</div></td>
                            <td>v{{ $document->version }}</td>
                            <td>{{ $document->uploaded_at?->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                <div class="iv-actions">
                                    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 fw-semibold" data-file-preview-url="{{ route('invoice-verification.transaction-documents.preview', $document) }}" data-file-preview-title="{{ $document->document_label ?: $document->documentType?->name }}">
                                        <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                        <span>Lihat Dokumen</span>
                                    </button>
                                    <form method="POST" action="{{ route('invoice-verification.vendor-reviews.update', $document) }}" class="d-inline-flex">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="ACCEPTED">
                                        <button class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('invoice-verification.vendor-reviews.update', $document) }}" class="d-inline-flex gap-1">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="REVISION_REQUIRED">
                                        <input name="notes" class="form-control form-control-sm" required placeholder="Catatan revisi" style="width: 180px;">
                                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Tidak ada dokumen vendor yang menunggu pengecekan hasil pekerjaan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-top">
            {{ $pendingDocuments->links() }}
        </div>
    </div>
</div>
@include('invoice-verification.components.file-preview-modal')
@endsection
