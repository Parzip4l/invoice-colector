@extends('layouts.vertical', ['subtitle' => 'Document Upload'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Documents', 'subtitle' => 'Upload Dokumen'])
@include('invoice-verification.partials.flash')

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-1">{{ $transaction->registration_number }}</h5>
        <p class="text-muted mb-0">Upload dokumen sesuai requirement tipe transaksi. Dokumen opsional boleh dikosongkan saat draft.</p>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('invoice-verification.transactions.documents.combined.store', $transaction) }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                @foreach ($documentTypes->where('source_type', \App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceType::VENDOR) as $documentType)
                    @php $existing = $transaction->latestDocuments->firstWhere('document_type_id', $documentType->id); @endphp
                    <div class="col-12">
                        <div class="border rounded-3 p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <div class="fw-semibold">{{ $documentType->name }}</div>
                                    <div class="text-muted small">{{ $documentType->is_required ? 'Wajib' : 'Opsional' }}{{ $existing ? ' · '.$existing->file_name : '' }}</div>
                                    <input type="hidden" name="attachments[{{ $loop->index }}][document_type_id]" value="{{ $documentType->id }}">
                                    <input type="hidden" name="attachments[{{ $loop->index }}][source_actor]" value="VENDOR">
                                    <input type="hidden" name="attachments[{{ $loop->index }}][document_label]" value="{{ $documentType->name }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">File</label>
                                    <input type="file" class="form-control" name="attachments[{{ $loop->index }}][file]" {{ $documentType->is_required && ! $existing ? 'required' : '' }}>
                                </div>
                                <div class="col-md-2">
                                    @if ($existing)
                                        <a class="btn btn-outline-primary d-inline-flex align-items-center gap-2 fw-semibold" href="{{ route('invoice-verification.transaction-documents.preview', $existing) }}" target="_blank">
                                            <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                            <span>Lihat Dokumen</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary">Upload Dokumen</button>
                <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>
@endsection
