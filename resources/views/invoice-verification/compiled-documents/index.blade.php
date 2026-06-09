@extends('layouts.vertical', ['subtitle' => 'Compiled Documents'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Finalization', 'subtitle' => 'Compiled Documents'])
@include('invoice-verification.partials.flash')

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transaksi</th>
                        <th>File</th>
                        <th>Total Files</th>
                        <th>Compiled At</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($compiledDocuments as $compiledDocument)
                        <tr>
                            <td>{{ $compiledDocument->transaction?->registration_number }}</td>
                            <td>{{ $compiledDocument->compiled_file_name }}</td>
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

        <div class="mt-3">
            {{ $compiledDocuments->links() }}
        </div>
    </div>
</div>
@endsection
