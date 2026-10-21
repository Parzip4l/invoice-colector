@extends('layouts.vertical', ['subtitle' => 'Document Upload'])

@section('css')
<style>
    .document-upload-shell { --upload-border: #e4e8ef; --upload-soft: #f7f8fa; --upload-accent: #ef2b2d; }
    .document-upload-shell .upload-intro { border: 1px solid var(--upload-border); border-radius: 18px; background: #fff; padding: 24px; margin-bottom: 20px; }
    .document-upload-shell .upload-intro-icon,
    .document-upload-shell .document-icon { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; border-radius: 14px; color: var(--upload-accent); background: #fff1f1; }
    .document-upload-shell .upload-intro-icon { width: 46px; height: 46px; font-size: 24px; }
    .document-upload-shell .document-card { border: 1px solid var(--upload-border); border-radius: 18px; background: #fff; padding: 22px; transition: border-color .2s ease, box-shadow .2s ease; }
    .document-upload-shell .document-card:hover { border-color: #d4dae4; box-shadow: 0 10px 28px rgba(15, 23, 42, .05); }
    .document-upload-shell .document-icon { width: 42px; height: 42px; font-size: 21px; }
    .document-upload-shell .document-title { color: #334155; font-size: 1rem; font-weight: 700; }
    .document-upload-shell .requirement-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 4px 9px; font-size: .72rem; font-weight: 700; color: #b42318; background: #fff1f1; }
    .document-upload-shell .requirement-badge.is-optional { color: #64748b; background: #f1f5f9; }
    .document-upload-shell .metadata-panel { border: 1px solid #edf0f4; border-radius: 14px; background: var(--upload-soft); padding: 16px; }
    .document-upload-shell .form-label { color: #475569; font-size: .82rem; font-weight: 700; margin-bottom: 7px; }
    .document-upload-shell .form-control { min-height: 45px; border-color: #dce2ea; border-radius: 11px; }
    .document-upload-shell .form-control:focus { border-color: #f4a2a3; box-shadow: 0 0 0 .2rem rgba(239, 43, 45, .08); }
    .document-upload-shell .file-zone { border: 1px dashed #cbd5e1; border-radius: 14px; background: #fbfcfd; padding: 14px; }
    .document-upload-shell .existing-file { display: flex; align-items: center; gap: 8px; color: #64748b; font-size: .8rem; margin-top: 9px; }
    .document-upload-shell .upload-actions { position: sticky; bottom: 14px; z-index: 5; display: flex; flex-wrap: wrap; gap: 10px; border: 1px solid var(--upload-border); border-radius: 15px; background: rgba(255, 255, 255, .96); box-shadow: 0 12px 30px rgba(15, 23, 42, .08); padding: 14px; margin-top: 20px; backdrop-filter: blur(10px); }
    @media (max-width: 767.98px) { .document-upload-shell .upload-intro, .document-upload-shell .document-card { padding: 17px; border-radius: 15px; } }
</style>
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Documents', 'subtitle' => 'Upload Dokumen'])
@include('invoice-verification.partials.flash')

<div class="document-upload-shell">
    <div class="upload-intro">
        <div class="d-flex align-items-start gap-3">
            <span class="upload-intro-icon"><iconify-icon icon="solar:cloud-upload-outline"></iconify-icon></span>
            <div>
                <div class="text-uppercase text-danger fw-bold small mb-1">
                    {{ $transaction->isPpaNonContract() ? 'Dokumen PPA Non Kontrak' : 'Dokumen '.$transaction->transactionType?->name }}
                </div>
                <h4 class="mb-1">{{ $transaction->registration_number }}</h4>
                <p class="text-muted mb-0">Lengkapi informasi dokumen dan unggah file yang sesuai. Dokumen opsional dapat ditambahkan kemudian.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('invoice-verification.transactions.documents.combined.store', $transaction) }}" enctype="multipart/form-data">
        @csrf
        <div class="d-grid gap-3">
            @foreach ($documentTypes->where('source_type', \App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceType::VENDOR) as $documentType)
                @php
                    $index = $loop->index;
                    $existing = $transaction->latestDocuments->firstWhere('document_type_id', $documentType->id);
                    $code = $documentType->code;
                    $isInvoice = $code === 'PNK_INVOICE';
                    $isReceipt = $code === 'PNK_KWITANSI';
                    $isTaxInvoice = $code === 'PNK_FAKTUR_PAJAK';
                    $isMemo = $code === 'PNK_MEMO_PERMOHONAN';
                    $isWorkReport = $code === 'PNK_LAPORAN_PEKERJAAN';

                    $hasMetadata = $isInvoice || $isReceipt || $isTaxInvoice || $isMemo;
                    $numberLabel = match (true) {
                        $isInvoice => 'Nomor Invoice',
                        $isReceipt => 'Nomor Kwitansi',
                        $isTaxInvoice => 'Nomor Faktur Pajak',
                        $isMemo => 'Nomor Memo',
                        default => 'Nomor Dokumen',
                    };
                    $dateLabel = match (true) {
                        $isInvoice => 'Tanggal Invoice',
                        $isReceipt => 'Tanggal Kwitansi',
                        $isTaxInvoice => 'Tanggal Faktur Pajak',
                        default => 'Tanggal Dokumen',
                    };

                    $documentNumber = old("attachments.$index.document_information.document_number", data_get($existing?->document_information_json, 'document_number', $isTaxInvoice ? $transaction->numberingRegister?->tax_invoice_number : null));
                    $documentDate = old("attachments.$index.document_information.document_date", data_get($existing?->document_information_json, 'document_date', match (true) {
                        $isInvoice => optional($transaction->invoiceMetadata?->invoice_date)->format('Y-m-d'),
                        $isTaxInvoice => optional($transaction->numberingRegister?->tax_invoice_date)->format('Y-m-d'),
                        default => null,
                    }));
                    $dppValue = old("attachments.$index.document_information.dpp_value", data_get($existing?->document_information_json, 'dpp_value', $isInvoice ? $transaction->invoiceMetadata?->invoice_value : null));
                    $ppnValue = old("attachments.$index.document_information.ppn_value", data_get($existing?->document_information_json, 'ppn_value', $isInvoice ? $transaction->invoiceMetadata?->ppn_value : null));
                    $icon = match (true) {
                        $isInvoice => 'solar:bill-list-outline',
                        $isReceipt => 'solar:receipt-outline',
                        $isTaxInvoice => 'solar:document-text-outline',
                        $isMemo => 'solar:letter-outline',
                        $isWorkReport => 'solar:clipboard-list-outline',
                        $code === 'PNK_LEMBAR_TANDA_TERIMA' => 'solar:clipboard-check-outline',
                        default => 'solar:folder-with-files-outline',
                    };
                @endphp

                <section class="document-card">
                    <input type="hidden" name="attachments[{{ $index }}][document_type_id]" value="{{ $documentType->id }}">
                    <input type="hidden" name="attachments[{{ $index }}][source_actor]" value="VENDOR">
                    <input type="hidden" name="attachments[{{ $index }}][document_label]" value="{{ $documentType->name }}">

                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="document-icon"><iconify-icon icon="{{ $icon }}"></iconify-icon></span>
                            <div>
                                <div class="document-title">{{ $documentType->name }}</div>
                                <span class="requirement-badge {{ $documentType->is_required ? '' : 'is-optional' }}">{{ $documentType->is_required ? 'Wajib' : 'Opsional' }}</span>
                            </div>
                        </div>
                        @if ($existing)
                            <a class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-2" href="{{ route('invoice-verification.transaction-documents.preview', $existing) }}" target="_blank">
                                <iconify-icon icon="solar:eye-outline" class="fs-17"></iconify-icon><span>Lihat dokumen</span>
                            </a>
                        @endif
                    </div>

                    @if ($hasMetadata)
                        <div class="metadata-panel mb-3">
                            <div class="row g-3">
                                @if ($isInvoice)
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">Nomor Invoice</label>
                                        <input type="text" class="form-control @error("attachments.$index.document_information.document_number") is-invalid @enderror" name="attachments[{{ $index }}][document_information][document_number]" value="{{ $documentNumber }}" placeholder="Masukkan nomor invoice" required>
                                        @error("attachments.$index.document_information.document_number")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">Tanggal Invoice</label>
                                        <input type="date" class="form-control @error("attachments.$index.document_information.document_date") is-invalid @enderror" name="attachments[{{ $index }}][document_information][document_date]" value="{{ $documentDate }}" required>
                                        @error("attachments.$index.document_information.document_date")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">Nominal DPP</label>
                                        <input type="text" inputmode="numeric" class="form-control @error("attachments.$index.document_information.dpp_value") is-invalid @enderror" name="attachments[{{ $index }}][document_information][dpp_value]" value="{{ $dppValue }}" placeholder="Rp 0" data-rupiah-input required>
                                        @error("attachments.$index.document_information.dpp_value")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">Nominal PPN</label>
                                        <input type="text" inputmode="numeric" class="form-control @error("attachments.$index.document_information.ppn_value") is-invalid @enderror" name="attachments[{{ $index }}][document_information][ppn_value]" value="{{ $ppnValue }}" placeholder="Rp 0" data-rupiah-input required>
                                        @error("attachments.$index.document_information.ppn_value")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                @elseif ($isReceipt)
                                    <div class="col-md-6">
                                        <label class="form-label">Nomor Kwitansi</label>
                                        <input type="text" class="form-control @error("attachments.$index.document_information.document_number") is-invalid @enderror" name="attachments[{{ $index }}][document_information][document_number]" value="{{ $documentNumber }}" placeholder="Masukkan nomor kwitansi" required>
                                        @error("attachments.$index.document_information.document_number")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Kwitansi</label>
                                        <input type="date" class="form-control @error("attachments.$index.document_information.document_date") is-invalid @enderror" name="attachments[{{ $index }}][document_information][document_date]" value="{{ $documentDate }}" required>
                                        @error("attachments.$index.document_information.document_date")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                @elseif ($isTaxInvoice)
                                    <div class="col-md-6">
                                        <label class="form-label">Nomor Faktur Pajak</label>
                                        <input type="text" class="form-control @error("attachments.$index.document_information.document_number") is-invalid @enderror" name="attachments[{{ $index }}][document_information][document_number]" value="{{ $documentNumber }}" placeholder="Masukkan nomor faktur pajak" required>
                                        @error("attachments.$index.document_information.document_number")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Faktur Pajak</label>
                                        <input type="date" class="form-control @error("attachments.$index.document_information.document_date") is-invalid @enderror" name="attachments[{{ $index }}][document_information][document_date]" value="{{ $documentDate }}" required>
                                        @error("attachments.$index.document_information.document_date")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                @elseif ($isMemo)
                                    <div class="col-12">
                                        <label class="form-label">Nomor Memo</label>
                                        <input type="text" class="form-control @error("attachments.$index.document_information.document_number") is-invalid @enderror" name="attachments[{{ $index }}][document_information][document_number]" value="{{ $documentNumber }}" placeholder="Masukkan nomor memo" required>
                                        @error("attachments.$index.document_information.document_number")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="file-zone">
                        <label class="form-label d-flex align-items-center gap-2"><iconify-icon icon="solar:paperclip-outline" class="fs-18"></iconify-icon><span>Pilih File</span></label>
                        <input type="file" class="form-control @error("attachments.$index.file") is-invalid @enderror" name="attachments[{{ $index }}][file]" accept=".pdf,.jpg,.jpeg,.png" {{ $documentType->is_required && ! $existing ? 'required' : '' }}>
                        @error("attachments.$index.file")<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($existing)
                            <div class="existing-file"><iconify-icon icon="solar:check-circle-outline" class="text-success fs-17"></iconify-icon><span>File tersimpan: {{ $existing->file_name }}. Pilih file baru hanya jika ingin mengganti.</span></div>
                        @else
                            <div class="text-muted small mt-2">Format PDF, JPG, JPEG, atau PNG sesuai batas ukuran sistem.</div>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>

        <div class="upload-actions">
            <button class="btn btn-primary d-inline-flex align-items-center gap-2 px-4"><iconify-icon icon="solar:cloud-upload-outline" class="fs-18"></iconify-icon><span>Upload Dokumen</span></button>
            <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn btn-outline-secondary px-4">Kembali</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
@include('invoice-verification.partials.rupiah-input')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[action*="combined"]');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        const fileInputs = form.querySelectorAll('input[type="file"]');
        let totalBytes = 0;
        let oversizedFiles = [];

        fileInputs.forEach(function (input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                totalBytes += file.size;
                // Check single file size > 30MB
                if (file.size > 30 * 1024 * 1024) {
                    oversizedFiles.push(`${file.name} (${(file.size / (1024 * 1024)).toFixed(1)} MB)`);
                }
            }
        });

        if (oversizedFiles.length > 0) {
            e.preventDefault();
            window.Swal.fire({
                icon: 'error',
                title: 'Ukuran file terlalu besar',
                text: 'File berikut melebihi batas maksimal 30 MB per file:\n• ' + oversizedFiles.join('\n• ') + '\n\nSilakan kompres file tersebut sebelum diunggah.',
                confirmButtonColor: '#ed1c24',
            });
            return false;
        }

        // Check total size > 150MB
        if (totalBytes > 150 * 1024 * 1024) {
            e.preventDefault();
            window.Swal.fire({
                icon: 'error',
                title: 'Total upload terlalu besar',
                text: `Total ukuran seluruh file yang diunggah (${(totalBytes / (1024 * 1024)).toFixed(1)} MB) melebihi batas maksimal server (150 MB). Silakan kompres file dokumen Anda terlebih dahulu.`,
                confirmButtonColor: '#ed1c24',
            });
            return false;
        }
    });
});
</script>
@endsection
