@extends('layouts.vertical', ['subtitle' => 'Accounting Verification'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Accounting Verification', 'subtitle' => 'Document Checks'])
@include('invoice-verification.partials.flash')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <h5 class="card-title mb-1">{{ $transaction->registration_number }}</h5>
            <p class="text-muted mb-0">Pilih status setiap bagian, lalu tekan tombol simpan untuk memproses hasil verifikasi.</p>
        </div>
        <button class="btn btn-primary" type="submit" form="accountingVerificationForm">
            Simpan Verifikasi
        </button>
    </div>
    <div class="card-body">
        @error('status')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
        <div class="alert alert-info border-0">
            Pilih keputusan per dokumen Invoicing. Keputusan baru dikirim ke sistem setelah klik <strong>Simpan Verifikasi</strong>.
        </div>
        <form id="accountingVerificationForm" method="POST" action="{{ route('invoice-verification.transactions.accounting-verifications.update', $transaction) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="administration_status" value="VALID">
            <div class="border rounded-3 p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                    <div>
                        <h5 class="mb-1">Administration</h5>
                        <p class="text-muted mb-0">Berisi Lembar PPA dan Lembar Checklist yang digenerate setelah review Admin User.</p>
                    </div>
                </div>
                <div class="row g-3">
                    @forelse ($transaction->generatedDocuments as $generatedDocument)
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold">{{ str($generatedDocument->document_code)->replace('_', ' ')->title() }}</div>
                                <div class="text-muted small">{{ $generatedDocument->document_number ?? '-' }}</div>
                                <div class="d-flex gap-2 flex-wrap mt-2">
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary d-inline-flex align-items-center gap-2"
                                        data-file-preview-url="{{ route('invoice-verification.generated-documents.preview', $generatedDocument) }}"
                                        data-file-preview-title="{{ str($generatedDocument->document_code)->replace('_', ' ')->title() }} - {{ $generatedDocument->document_number ?? $transaction->registration_number }}"
                                    >
                                        <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                        <span>Lihat Dokumen</span>
                                    </button>
                                    <a href="{{ route('invoice-verification.generated-documents.show', $generatedDocument) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-muted">Belum ada Lembar PPA yang digenerate.</div>
                    @endforelse

                    @if ($transaction->ppaVerificationSheet?->file_path)
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold">Lembar Checklist PPA</div>
                                <div class="text-muted small">{{ $transaction->ppaVerificationSheet->file_name ?? 'Checklist PPA' }}</div>
                                <div class="d-flex gap-2 flex-wrap mt-2">
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary d-inline-flex align-items-center gap-2"
                                        data-file-preview-url="{{ route('invoice-verification.transactions.ppa-verification-sheets.preview', $transaction) }}"
                                        data-file-preview-title="Lembar Checklist PPA - {{ $transaction->registration_number }}"
                                    >
                                        <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                        <span>Lihat Dokumen</span>
                                    </button>
                                    <a href="{{ route('invoice-verification.transactions.ppa-verification-sheets.edit', $transaction) }}" class="btn btn-sm btn-outline-secondary">Detail Checklist</a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="border rounded-3 p-3">
                <h5 class="mb-1">Invoicing</h5>
                <p class="text-muted mb-3">Berisi seluruh dokumen tagihan yang diinput dan diupload vendor.</p>
            <div class="table-responsive">
                <table class="table table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Dokumen</th>
                            <th>Versi</th>
                            <th>Preview</th>
                            <th>Status Verifikasi</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($verification->items as $index => $item)
                            @php
                                $selectedItemStatus = old("items.$index.status", $item->status?->value ?? 'VALID');
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->transactionDocument?->document_label ?: $item->transactionDocument?->documentType?->name }}</div>
                                    <div class="text-muted small">{{ $item->transactionDocument?->file_name }}</div>
                                    <input type="hidden" name="items[{{ $index }}][transaction_document_id]" value="{{ $item->transaction_document_id }}">
                                </td>
                                <td>v{{ $item->transactionDocument?->version }}</td>
                                <td>
                                    @if ($item->transactionDocument)
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary d-inline-flex align-items-center gap-2"
                                            data-file-preview-url="{{ route('invoice-verification.transaction-documents.preview', $item->transactionDocument) }}"
                                            data-file-preview-title="{{ $item->transactionDocument->document_label ?: $item->transactionDocument->documentType?->name }}"
                                        >
                                            <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                            <span>Lihat Dokumen</span>
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 flex-wrap" data-status-toggle-group>
                                        <input type="hidden" name="items[{{ $index }}][status]" value="{{ $selectedItemStatus }}" data-status-toggle-input>
                                        <button
                                            type="button"
                                            class="btn btn-sm {{ $selectedItemStatus === 'VALID' ? 'btn-success' : 'btn-outline-success' }}"
                                            data-status-toggle-option
                                            data-status-value="VALID"
                                            data-status-variant="success"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm {{ $selectedItemStatus === 'REVISION_REQUIRED' ? 'btn-danger' : 'btn-outline-danger' }}"
                                            data-status-toggle-option
                                            data-status-value="REVISION_REQUIRED"
                                            data-status-variant="danger"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <textarea
                                        name="items[{{ $index }}][notes]"
                                        class="form-control"
                                        rows="2"
                                        placeholder="Wajib diisi jika dokumen direject"
                                    >{{ $item->notes }}</textarea>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-8">
                    <label class="form-label">Catatan Umum</label>
                    <textarea class="form-control" rows="3" name="notes">{{ old('notes', $verification->notes) }}</textarea>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Simpan Verifikasi Akuntansi</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@include('invoice-verification.components.file-preview-modal')

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleGroups = document.querySelectorAll('[data-status-toggle-group]');

        const updateButtonState = (button, isActive) => {
            const variant = button.dataset.statusVariant;
            button.classList.toggle(`btn-${variant}`, isActive);
            button.classList.toggle(`btn-outline-${variant}`, !isActive);
        };

        toggleGroups.forEach((group) => {
            const input = group.querySelector('[data-status-toggle-input]');
            const buttons = group.querySelectorAll('[data-status-toggle-option]');

            const applySelection = (value) => {
                if (!input) {
                    return;
                }

                input.value = value;

                buttons.forEach((button) => {
                    updateButtonState(button, button.dataset.statusValue === value);
                });
            };

            buttons.forEach((button) => {
                button.addEventListener('click', function () {
                    applySelection(this.dataset.statusValue);
                });
            });

            applySelection(input?.value ?? 'VALID');
        });
    });
</script>
@endsection
