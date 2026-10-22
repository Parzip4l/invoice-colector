@extends('layouts.vertical', ['subtitle' => 'Compiled Document Detail'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Compiled Documents', 'subtitle' => 'Detail'])
@include('invoice-verification.partials.flash')

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="card-title mb-1">{{ $compiledDocument->transaction?->registration_number }}</h5>
                        <div class="text-muted small">PDF dibuat sementara ketika di-download dan tidak disimpan permanen di server.</div>
                    </div>
                    <a href="{{ route('invoice-verification.compiled-documents.download', $compiledDocument) }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <iconify-icon icon="solar:download-minimalistic-outline" class="fs-18"></iconify-icon>
                        <span>Download PDF Kompilasi</span>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Nama File Download</small>
                            <div class="fw-semibold">{{ $compiledDocument->compiled_file_name }}</div>
                            <div class="text-success small">On-demand · tidak tersimpan di server</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Manifest Dibuat</small>
                            <div class="fw-semibold">{{ $compiledDocument->compiled_at?->format('d M Y H:i') }}</div>
                            <div class="text-muted small">By {{ $compiledDocument->compiler?->name }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Penyimpanan</small>
                            <div class="fw-semibold">File sumber saja</div>
                            <div class="text-muted small">Hasil gabungan dihapus otomatis setelah download</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Included Items</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-centered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Source</th>
                                <th>Reference</th>
                                <th>Included As</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($compiledDocument->items as $item)
                                <tr>
                                    <td>{{ $item->sort_order }}</td>
                                    <td>{{ str($item->source_type)->replace('_', ' ')->title() }}</td>
                                    <td>{{ $item->source_id }}</td>
                                    <td>{{ $item->included_as }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada item kompilasi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
