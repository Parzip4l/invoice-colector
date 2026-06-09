@extends('layouts.vertical', ['subtitle' => 'Admin Review'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Admin Review', 'subtitle' => 'Pengecekan Tagihan Vendor'])
@include('invoice-verification.partials.flash')

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transaksi</th>
                        <th>Dokumen</th>
                        <th>Vendor</th>
                        <th>Versi</th>
                        <th>Uploaded</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendingDocuments as $document)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $document->transaction?->registration_number }}</div>
                                <div class="text-muted small">{{ $document->transaction?->title }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $document->document_label ?: $document->documentType?->name }}</div>
                                @if ($document->document_information_json)
                                    <div class="text-muted small">
                                        {{ $document->document_information_json['document_number'] ?? '-' }}
                                        @if (!empty($document->document_information_json['document_date']))
                                            · {{ \Illuminate\Support\Carbon::parse($document->document_information_json['document_date'])->format('d M Y') }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>{{ $document->transaction?->vendor?->name ?? '-' }}</td>
                            <td>v{{ $document->version }}</td>
                            <td>{{ $document->uploaded_at?->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                <a
                                    href="{{ route('invoice-verification.transaction-documents.preview', $document) }}"
                                    class="btn btn-sm btn-outline-primary"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    Preview
                                </a>
                                <form method="POST" action="{{ route('invoice-verification.vendor-reviews.update', $document) }}" class="d-inline-flex gap-2 align-items-start">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="ACCEPTED">
                                    <button class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('invoice-verification.vendor-reviews.update', $document) }}" class="d-inline-flex gap-2 align-items-start ms-1">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="REVISION_REQUIRED">
                                    <textarea
                                        name="notes"
                                        class="form-control form-control-sm"
                                        rows="2"
                                        required
                                        placeholder="Keterangan revisi untuk vendor"
                                        style="min-width: 220px;"
                                    ></textarea>
                                    <button class="btn btn-sm btn-outline-danger">Reject</button>
                                </form>
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

        <div class="mt-3">
            {{ $pendingDocuments->links() }}
        </div>
    </div>
</div>
@endsection
