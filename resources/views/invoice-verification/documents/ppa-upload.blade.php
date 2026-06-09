@extends('layouts.vertical', ['subtitle' => 'PPA Document Upload'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Documents', 'subtitle' => 'PPA Detailed Upload'])
@include('invoice-verification.partials.flash')

@php
    $isRevision = $transaction->status?->value === 'REVISION_IN_PROGRESS';
    $rejectedDocuments = $transaction->latestDocuments
        ->filter(fn ($document) => ($document->status?->value ?? $document->status) === 'REVISION_REQUIRED')
        ->keyBy('document_type_id');
    $uploadableDocumentTypes = $documentTypes->whereNotIn('code', ['PPA_LEMBAR_AWAL', 'PPA_LEMBAR_VERIFIKASI']);

    if ($isRevision) {
        $uploadableDocumentTypes = $uploadableDocumentTypes->filter(fn ($documentType) => $rejectedDocuments->has($documentType->id));
    }
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ $transaction->registration_number }}</h5>
            <p class="text-muted mb-0">
                {{ $isRevision ? 'Upload ulang hanya untuk dokumen yang direject pada workflow.' : 'Input data tagihan dan upload dokumen PPA dilakukan oleh vendor.' }}
            </p>
        </div>
        <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
    <div class="card-body">
        @if ($isRevision)
            <div class="alert alert-warning border-0">
                <div class="fw-semibold">Dokumen perlu revisi</div>
                <div>Perbaiki informasi dokumen dan upload file pengganti. File versi lama akan tetap tersimpan sebagai riwayat, sedangkan file baru menjadi versi terbaru.</div>
            </div>
        @endif

        <form method="POST" action="{{ route('invoice-verification.transactions.documents.ppa.store', $transaction) }}" enctype="multipart/form-data">
            @csrf
            <div class="border rounded-3 p-3 mb-3">
                <h5 class="mb-1">Informasi Tagihan</h5>
                <p class="text-muted mb-3">Data ini akan dipakai untuk generate Lembar PPA, Lembar Verifikasi, compile dokumen, dan penomoran.</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nomor Invoice</label>
                        <input type="text" class="form-control" name="invoice_number" value="{{ old('invoice_number', $transaction->invoiceMetadata?->invoice_number) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Invoice</label>
                        <input type="date" class="form-control" name="invoice_date" value="{{ old('invoice_date', optional($transaction->invoiceMetadata?->invoice_date)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Diterima</label>
                        <input type="date" class="form-control" name="received_date" value="{{ old('received_date', optional($transaction->invoiceMetadata?->received_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bank</label>
                        <input type="text" class="form-control" name="bank_name" value="{{ old('bank_name', $transaction->invoiceMetadata?->bank_name ?? $transaction->vendor?->defaultBank?->name) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nomor Rekening</label>
                        <input type="text" class="form-control" name="account_number" value="{{ old('account_number', $transaction->invoiceMetadata?->account_number ?? $transaction->vendor?->default_account_number) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nilai Invoice</label>
                        <input type="number" step="0.01" class="form-control" name="invoice_value" value="{{ old('invoice_value', $transaction->invoiceMetadata?->invoice_value) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PPN</label>
                        <input type="number" step="0.01" class="form-control" name="ppn_value" value="{{ old('ppn_value', $transaction->invoiceMetadata?->ppn_value) }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Uraian Transaksi</label>
                        <input type="text" class="form-control" name="description" value="{{ old('description', $transaction->invoiceMetadata?->description ?? $transaction->description) }}">
                    </div>
                </div>
            </div>

            <div class="row g-3">
                @forelse ($uploadableDocumentTypes as $index => $documentType)
                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h6 class="mb-1">{{ $documentType->name }}</h6>
                                    <small class="text-muted">{{ $documentType->source_type->value }}</small>
                                </div>
                                @if ($documentType->is_required)
                                    <span class="badge bg-danger-subtle text-danger">Required</span>
                                @endif
                            </div>
                            @php
                                $usesRegisteredMemo = $documentType->code === 'PPA_MEMO_PERMOHONAN' && $transaction->memoRequest?->file_path;
                                $usesRegisteredAgreement = $documentType->code === 'PPA_PERJANJIAN' && $transaction->agreementReference?->file_path;
                                $rejectedDocument = $rejectedDocuments->get($documentType->id);
                            @endphp
                            @if ($usesRegisteredMemo)
                                <div class="alert alert-light border mb-0">
                                    Memo memakai referensi terdaftar: <strong>{{ $transaction->memoRequest->memo_number }}</strong>.
                                    <a href="{{ route('invoice-verification.master-data.memo-requests.download', $transaction->memoRequest) }}" class="btn btn-sm btn-link px-0 ms-1">Lihat File</a>
                                </div>
                            @elseif ($usesRegisteredAgreement)
                                <div class="alert alert-light border mb-0">
                                    Kontrak memakai referensi terdaftar: <strong>{{ $transaction->agreementReference->contract_number }}</strong>.
                                    <a href="{{ route('invoice-verification.master-data.agreement-references.download', $transaction->agreementReference) }}" class="btn btn-sm btn-link px-0 ms-1">Lihat File</a>
                                </div>
                            @else
                                <input type="hidden" name="documents[{{ $index }}][document_type_id]" value="{{ $documentType->id }}">
                                @if ($rejectedDocument)
                                    @php
                                        $accountingRevisionNote = $rejectedDocument->accountingVerificationItems
                                            ->filter(fn ($item) => in_array($item->status?->value ?? $item->status, ['REVISION_REQUIRED', 'MISMATCH'], true))
                                            ->sortByDesc('verified_at')
                                            ->first()?->notes;
                                        $revisionNote = $accountingRevisionNote
                                            ?: $rejectedDocument->vendorReview?->notes
                                            ?: 'Dokumen perlu diperbaiki.';
                                    @endphp
                                    <div class="alert alert-danger-subtle text-danger border border-danger-subtle">
                                        <div class="fw-semibold">Catatan Reject</div>
                                        <div>{{ $revisionNote }}</div>
                                        <a
                                            href="{{ route('invoice-verification.transaction-documents.preview', $rejectedDocument) }}"
                                            class="btn btn-sm btn-outline-danger mt-2"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            Preview File Lama
                                        </a>
                                    </div>
                                @endif
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nomor Dokumen</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            name="documents[{{ $index }}][document_information][document_number]"
                                            value="{{ old("documents.$index.document_information.document_number", data_get($rejectedDocument?->document_information_json, 'document_number')) }}"
                                            placeholder="Contoh: BAPP-001/IV/2026"
                                        >
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Dokumen</label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            name="documents[{{ $index }}][document_information][document_date]"
                                            value="{{ old("documents.$index.document_information.document_date", data_get($rejectedDocument?->document_information_json, 'document_date')) }}"
                                        >
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Keterangan Dokumen</label>
                                        <textarea
                                            class="form-control"
                                            rows="2"
                                            name="documents[{{ $index }}][document_information][notes]"
                                            placeholder="Informasi tambahan terkait dokumen ini"
                                        >{{ old("documents.$index.document_information.notes", data_get($rejectedDocument?->document_information_json, 'notes')) }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">File Dokumen</label>
                                        <input type="file" class="form-control" name="documents[{{ $index }}][file]">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center text-muted py-4 border rounded-3">
                            Tidak ada dokumen yang perlu diupload ulang.
                        </div>
                    </div>
                @endforelse
            </div>
            <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">Memo dan kontrak yang sudah terdaftar di master data akan dipakai otomatis sebagai referensi.</small>
                <button class="btn btn-primary">Upload Dokumen PPA</button>
            </div>
        </form>
    </div>
</div>
@endsection
