@extends('layouts.vertical', ['subtitle' => 'Archive'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Archive', 'subtitle' => 'Final Bundles'])
@include('invoice-verification.partials.flash')

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transaksi</th>
                        <th>Compiled File</th>
                        <th>Archive Path</th>
                        <th>Archived At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($archives as $archive)
                        <tr>
                            <td>{{ $archive->transaction?->registration_number }}</td>
                            <td>{{ $archive->compiled_file_name }}</td>
                            <td>{{ $archive->archive_path }}</td>
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

        <div class="mt-3">
            {{ $archives->links() }}
        </div>
    </div>
</div>
@endsection
