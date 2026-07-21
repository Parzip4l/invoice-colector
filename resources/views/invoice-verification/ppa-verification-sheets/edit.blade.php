@extends('layouts.vertical', ['subtitle' => 'PPA Verification Sheet'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'PPA Verification Sheet', 'subtitle' => 'Checklist Form'])
@include('invoice-verification.partials.flash')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ $transaction->registration_number }}</h5>
            <p class="text-muted mb-0">Checklist terstruktur untuk membandingkan kelengkapan dokumen PPA dengan upload aktual.</p>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('invoice-verification.transactions.ppa-verification-sheets.submit', $transaction) }}">
                @csrf
                <button class="btn btn-primary">Submit Checklist</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('invoice-verification.transactions.ppa-verification-sheets.update', $transaction) }}">
            @csrf
            @method('PUT')

            <div class="table-responsive">
                <table class="table table-centered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Dokumen</th>
                            <th>Attachment Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sheet->items as $index => $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->documentType?->name }}</div>
                                    <input type="hidden" name="items[{{ $index }}][document_type_id]" value="{{ $item->document_type_id }}">
                                </td>
                                <td>
                                    <select class="form-select" name="items[{{ $index }}][attachment_status]">
                                        <option value="ATTACHED" @selected(($item->attachment_status?->value ?? null) === 'ATTACHED')>ATTACHED</option>
                                        <option value="NOT_ATTACHED" @selected(($item->attachment_status?->value ?? null) === 'NOT_ATTACHED')>NOT_ATTACHED</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="items[{{ $index }}][notes]" value="{{ $item->notes }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div>
                    @include('invoice-verification.components.status-badge', ['value' => $sheet->status])
                    @if ($sheet->rejection_notes)
                        <p class="text-danger mt-2 mb-0">Catatan penolakan: {{ $sheet->rejection_notes }}</p>
                    @endif
                </div>
                <button class="btn btn-outline-primary">Simpan Checklist</button>
            </div>
        </form>
    </div>
</div>
@endsection
