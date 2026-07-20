@extends('layouts.vertical', ['subtitle' => 'Compiled Documents'])

@section('css')
@include('invoice-verification.partials.table-ui')
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Data Penomoran', 'subtitle' => 'Dokumen Kompilasi'])
@include('invoice-verification.partials.flash')

<div class="card iv-table-card">
    <div class="card-header bg-white border-bottom">
        <form method="GET" class="iv-table-toolbar" style="--iv-filter-count: 0;">
            <div>
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><iconify-icon icon="solar:magnifer-outline" class="fs-18"></iconify-icon></span>
                    <input type="search" class="form-control" name="search" value="{{ $search }}" placeholder="Transaksi atau nama file">
                </div>
            </div>
            <button class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1"><iconify-icon icon="solar:filter-outline" class="fs-18"></iconify-icon><span>Filter</span></button>
            <a href="{{ route('invoice-verification.compiled-documents.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1"><iconify-icon icon="solar:restart-outline" class="fs-18"></iconify-icon><span>Reset</span></a>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 iv-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.compiled-documents.index', 'column' => 'transaction', 'label' => 'Transaksi'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.compiled-documents.index', 'column' => 'compiled_file_name', 'label' => 'File'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.compiled-documents.index', 'column' => 'total_files', 'label' => 'Total Files'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.compiled-documents.index', 'column' => 'compiled_at', 'label' => 'Compiled At'])</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($compiledDocuments as $compiledDocument)
                        <tr>
                            <td class="ps-4">{{ $compiledDocument->transaction?->registration_number }}</td>
                            <td><div class="text-truncate iv-cell-truncate" title="{{ $compiledDocument->compiled_file_name }}">{{ $compiledDocument->compiled_file_name }}</div></td>
                            <td>{{ $compiledDocument->total_files }}</td>
                            <td>{{ $compiledDocument->compiled_at?->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('invoice-verification.compiled-documents.show', $compiledDocument) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada dokumen kompilasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-top">
            {{ $compiledDocuments->links() }}
        </div>
    </div>
</div>
@endsection
