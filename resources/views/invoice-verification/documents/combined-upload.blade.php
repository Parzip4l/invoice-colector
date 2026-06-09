@extends('layouts.vertical', ['subtitle' => 'Combined Document Upload'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Documents', 'subtitle' => 'Combined Upload'])
@include('invoice-verification.partials.flash')

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-1">{{ $transaction->registration_number }}</h5>
        <p class="text-muted mb-0">Satu form upload untuk SPU, SPUK, dan Kas Kecil. Setiap file tetap disimpan sebagai record dokumen terpisah.</p>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('invoice-verification.transactions.documents.combined.store', $transaction) }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                @for ($i = 0; $i < 3; $i++)
                    <div class="col-12">
                        <div class="border rounded-3 p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Document Type</label>
                                    <select name="attachments[{{ $i }}][document_type_id]" class="form-select">
                                        @foreach ($documentTypes as $documentType)
                                            <option value="{{ $documentType->id }}">{{ $documentType->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Source Actor</label>
                                    <select name="attachments[{{ $i }}][source_actor]" class="form-select">
                                        <option value="USER_DIVISI">User Divisi</option>
                                        <option value="VENDOR">Vendor</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Document Label</label>
                                    <input type="text" class="form-control" name="attachments[{{ $i }}][document_label]" placeholder="contoh: Invoice Termin 1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">File</label>
                                    <input type="file" class="form-control" name="attachments[{{ $i }}][file]">
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
            <div class="mt-3">
                <button class="btn btn-primary">Upload Dokumen</button>
            </div>
        </form>
    </div>
</div>
@endsection
