@extends('layouts.vertical', ['subtitle' => 'Archive'])

@section('css')
@include('invoice-verification.partials.table-ui')
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Arsip', 'subtitle' => 'Dokumen Kompilasi'])
@include('invoice-verification.partials.flash')

<div class="card iv-table-card">
    <div class="card-header bg-white border-bottom">
        <form method="GET" class="iv-table-toolbar" style="--iv-filter-count: 0;">
            <div>
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><iconify-icon icon="solar:magnifer-outline" class="fs-18"></iconify-icon></span>
                    <input type="search" class="form-control" name="search" value="{{ $search }}" placeholder="Transaksi, file, path arsip">
                </div>
            </div>
            <button class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1"><iconify-icon icon="solar:filter-outline" class="fs-18"></iconify-icon><span>Filter</span></button>
            <a href="{{ route('invoice-verification.archive.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1"><iconify-icon icon="solar:restart-outline" class="fs-18"></iconify-icon><span>Reset</span></a>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 iv-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.archive.index', 'column' => 'transaction', 'label' => 'Transaksi'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.archive.index', 'column' => 'compiled_file_name', 'label' => 'Compiled File'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.archive.index', 'column' => 'archive_path', 'label' => 'Archive Path'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.archive.index', 'column' => 'archived_at', 'label' => 'Archived At'])</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($archives as $archive)
                        <tr>
                            <td class="ps-4">{{ $archive->transaction?->registration_number }}</td>
                            <td><div class="text-truncate iv-cell-truncate" title="{{ $archive->compiled_file_name }}">{{ $archive->compiled_file_name }}</div></td>
                            <td><div class="text-truncate iv-cell-truncate" style="--iv-cell-width: 420px;" title="{{ $archive->archive_path }}">{{ $archive->archive_path }}</div></td>
                            <td>{{ $archive->archived_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada arsip final.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-top">
            {{ $archives->links() }}
        </div>
    </div>
</div>
@endsection
