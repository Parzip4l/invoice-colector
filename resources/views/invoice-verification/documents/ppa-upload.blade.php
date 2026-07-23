@extends('layouts.vertical', ['subtitle' => 'PPA Document Upload'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Documents', 'subtitle' => 'PPA Detailed Upload'])
@include('invoice-verification.partials.flash')

@php
    $isRevision = in_array($transaction->status?->value, [
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value,
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::REVISION_IN_PROGRESS->value,
    ], true);
    $rejectedDocuments = $transaction->latestDocuments
        ->filter(fn ($document) => ($document->status?->value ?? $document->status) === 'REVISION_REQUIRED')
        ->keyBy('document_type_id');
    $internalCodes = ['PPA_MEMO_PERMOHONAN', 'PPA_PERJANJIAN'];
    $uploadableDocumentTypes = $documentTypes->whereNotIn('code', ['PPA_LEMBAR_AWAL', 'PPA_LEMBAR_VERIFIKASI']);

    if ($isRevision) {
        $uploadableDocumentTypes = $uploadableDocumentTypes->filter(fn ($documentType) => $rejectedDocuments->has($documentType->id));
    }

    $vendorDocumentTypes = $uploadableDocumentTypes->reject(fn ($documentType) => in_array($documentType->code, $internalCodes, true));
    $requiredVendorDocuments = $vendorDocumentTypes->where('is_required', true);
    $internalReferenceCount = collect([
        $transaction->memoRequest?->file_path,
        $transaction->agreementReference?->file_path,
    ])->filter()->count();

    $completedRequiredCount = $requiredVendorDocuments
        ->filter(fn ($documentType) => old("documents.$documentType->id.file") || $transaction->latestDocuments->firstWhere('document_type_id', $documentType->id))
        ->count();
    $requiredCount = $requiredVendorDocuments->count();
    $missingRequiredCount = max($requiredCount - $completedRequiredCount, 0);
    $completionPercent = $requiredCount > 0 ? (int) round(($completedRequiredCount / $requiredCount) * 100) : 100;
    $allowedMimes = collect(config('invoice_verification.storage.allowed_mimes', ['pdf', 'jpg', 'jpeg', 'png']))
        ->map(fn ($mime) => strtoupper($mime))
        ->join(', ');
    $allowedExtensions = collect(config('invoice_verification.storage.allowed_mimes', ['pdf', 'jpg', 'jpeg', 'png']))
        ->map(fn ($mime) => strtolower($mime))
        ->values();
    $maxUploadMb = (int) ceil(((int) config('invoice_verification.storage.max_upload_kb', 10240)) / 1024);
    $maxUploadBytes = (int) config('invoice_verification.storage.max_upload_kb', 10240) * 1024;
    $documentAccordionId = 'ppaDocumentAccordion';
@endphp

<style>
    .ppa-upload-page {
        --signal-primary: #e21a1a;
        --signal-primary-dark: #b91515;
        --signal-gold: #c07f20;
        --signal-border: rgba(31, 41, 55, .09);
        --signal-muted: #6b7280;
        --signal-heading: #172033;
        --signal-shadow: 0 14px 34px rgba(27, 36, 54, .08);
        padding-bottom: 92px;
        color: var(--signal-heading);
    }

    .ppa-upload-page .workflow-card {
        border: 1px solid var(--signal-border);
        border-radius: 16px;
        background: #ffffff;
        box-shadow: var(--signal-shadow);
    }

    .ppa-upload-page .page-hero {
        padding: 22px 24px;
        background: linear-gradient(135deg, rgba(226, 26, 26, .08), rgba(255, 255, 255, .96) 52%, rgba(192, 127, 32, .08));
    }

    .ppa-upload-page .section-card .card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 18px 20px 12px;
        border-color: var(--signal-border);
        background: transparent;
    }

    .ppa-upload-page .section-card .card-body {
        padding: 18px 20px 20px;
    }

    .ppa-upload-page .section-kicker,
    .ppa-upload-page .field-label {
        color: var(--signal-muted);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .ppa-upload-page .page-title {
        margin: 0;
        font-size: 1.45rem;
        font-weight: 750;
        letter-spacing: 0;
    }

    .ppa-upload-page .page-subtitle {
        color: var(--signal-muted);
        font-size: .92rem;
    }

    .ppa-upload-page .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .ppa-upload-page .summary-card {
        border: 1px solid var(--signal-border);
        border-radius: 14px;
        background: rgba(255, 255, 255, .76);
        padding: 14px;
    }

    .ppa-upload-page .summary-value {
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1;
    }

    .ppa-upload-page .progress {
        height: 10px;
        border-radius: 999px;
        background: #eef2f7;
    }

    .ppa-upload-page .progress-bar {
        background: var(--signal-primary);
    }

    .ppa-upload-page .billing-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .ppa-upload-page .billing-field.full {
        grid-column: span 2;
    }

    .ppa-upload-page .form-label {
        margin-bottom: 8px;
        color: #53677d;
        font-weight: 700;
    }

    .ppa-upload-page .required-mark {
        color: var(--signal-primary);
        font-weight: 800;
        margin-left: 3px;
    }

    .ppa-upload-page .form-control,
    .ppa-upload-page .form-select {
        min-height: 44px;
        border-color: var(--signal-border);
        border-radius: 10px;
        background: #fbfcfe;
    }

    .ppa-upload-page textarea.form-control {
        min-height: 88px;
    }

    .ppa-upload-page .btn {
        border-radius: 10px;
        font-weight: 650;
    }

    .ppa-upload-page .btn-primary {
        border-color: var(--signal-primary);
        background: var(--signal-primary);
        box-shadow: 0 8px 18px rgba(226, 26, 26, .16);
    }

    .ppa-upload-page .btn-primary:hover {
        border-color: var(--signal-primary-dark);
        background: var(--signal-primary-dark);
    }

    .ppa-upload-page .btn-outline-primary {
        border-color: rgba(226, 26, 26, .32);
        color: var(--signal-primary);
    }

    .ppa-upload-page .btn-outline-primary:hover {
        border-color: var(--signal-primary);
        background: var(--signal-primary);
        color: #ffffff;
    }

    .ppa-upload-page .badge {
        border-radius: 999px;
        padding: .42rem .68rem;
        font-weight: 700;
    }

    .ppa-upload-page .document-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .ppa-upload-page .status-filter {
        display: inline-flex;
        gap: 6px;
        padding: 4px;
        border: 1px solid var(--signal-border);
        border-radius: 999px;
        background: #f9fafb;
    }

    .ppa-upload-page .filter-pill {
        border: 0;
        border-radius: 999px;
        background: transparent;
        color: var(--signal-muted);
        font-weight: 700;
        padding: 7px 12px;
    }

    .ppa-upload-page .filter-pill.is-active {
        background: #ffffff;
        color: var(--signal-primary);
        box-shadow: 0 4px 12px rgba(27, 36, 54, .08);
    }

    .ppa-upload-page .accordion-item {
        overflow: hidden;
        border: 1px solid var(--signal-border);
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 8px 22px rgba(27, 36, 54, .05);
    }

    .ppa-upload-page .accordion-item + .accordion-item {
        margin-top: 12px;
    }

    .ppa-upload-page .accordion-button {
        gap: 12px;
        padding: 16px 18px;
        background: #ffffff;
        box-shadow: none;
    }

    .ppa-upload-page .accordion-button:not(.collapsed) {
        color: var(--signal-heading);
        background: #fbfcfe;
    }

    .ppa-upload-page .document-index {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: rgba(226, 26, 26, .09);
        color: var(--signal-primary);
        font-weight: 800;
    }

    .ppa-upload-page .document-title {
        font-weight: 800;
    }

    .ppa-upload-page .document-meta {
        color: var(--signal-muted);
        font-size: .84rem;
    }

    .ppa-upload-page .accordion-body {
        padding: 18px;
        border-top: 1px solid var(--signal-border);
        background: #ffffff;
    }

    .ppa-upload-page .vendor-document-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .ppa-upload-page .vendor-document-grid .full {
        grid-column: 1 / -1;
    }

    .ppa-upload-page .upload-box {
        position: relative;
        border: 1.5px dashed rgba(83, 103, 125, .35);
        border-radius: 14px;
        background: #fbfcfe;
        padding: 20px;
        transition: border-color .18s ease, background .18s ease;
    }

    .ppa-upload-page .upload-box.is-dragover,
    .ppa-upload-page .upload-box:hover {
        border-color: rgba(226, 26, 26, .48);
        background: rgba(226, 26, 26, .025);
    }

    .ppa-upload-page .upload-input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .ppa-upload-page .upload-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(226, 26, 26, .09);
        color: var(--signal-primary);
    }

    .ppa-upload-page .file-state {
        display: none;
        margin-top: 14px;
        border: 1px solid var(--signal-border);
        border-radius: 12px;
        background: #ffffff;
        padding: 12px;
    }

    .ppa-upload-page .upload-box.has-file .file-state {
        display: flex;
    }

    .ppa-upload-page .internal-reference {
        border: 1px solid var(--signal-border);
        border-radius: 14px;
        background: #fbfcfe;
        padding: 16px;
    }

    .ppa-upload-page .validation-message {
        display: none;
        margin-top: 8px;
        color: #dc2626;
        font-size: .82rem;
        font-weight: 650;
    }

    .ppa-upload-page .is-invalid-local .validation-message {
        display: block;
    }

    .ppa-upload-page .sticky-submit-bar {
        position: fixed;
        bottom: 0;
        right: 24px;
        left: calc(var(--bs-sidebar-width, 280px) + 24px);
        z-index: 20;
        border: 1px solid var(--signal-border);
        border-bottom: 0;
        border-radius: 16px 16px 0 0;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 -14px 34px rgba(27, 36, 54, .10);
        backdrop-filter: blur(10px);
        padding: 16px 20px calc(16px + env(safe-area-inset-bottom));
    }

    @media (max-width: 1199.98px) {
        .ppa-upload-page .summary-grid,
        .ppa-upload-page .billing-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .ppa-upload-page {
            padding-bottom: 132px;
        }

        .ppa-upload-page .page-hero,
        .ppa-upload-page .section-card .card-header,
        .ppa-upload-page .section-card .card-body {
            padding-left: 16px;
            padding-right: 16px;
        }

        .ppa-upload-page .summary-grid,
        .ppa-upload-page .billing-grid,
        .ppa-upload-page .vendor-document-grid {
            grid-template-columns: 1fr;
        }

        .ppa-upload-page .billing-field.full {
            grid-column: auto;
        }

        .ppa-upload-page .sticky-submit-bar {
            left: 12px;
            right: 12px;
            border-radius: 14px;
        }
    }
</style>

<div class="ppa-upload-page">
    <form
        method="POST"
        action="{{ route('invoice-verification.transactions.documents.ppa.store', $transaction) }}"
        enctype="multipart/form-data"
        data-ppa-upload-form
    >
        @csrf

        <div class="workflow-card page-hero mb-4">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
                <div>
                    <div class="section-kicker mb-2">Vendor Submission</div>
                    <h3 class="page-title">PPA Detailed Upload</h3>
                    <div class="page-subtitle mt-1">
                        Input data tagihan dan lengkapi dokumen PPA sebelum submit ke Accounting.
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                        <span class="badge bg-light text-dark border">{{ $transaction->registration_number }}</span>
                        @if ($isRevision)
                            <span class="badge bg-warning-subtle text-warning">Revision Required</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn btn-outline-secondary">
                    <iconify-icon icon="solar:arrow-left-outline" class="me-1"></iconify-icon>Kembali
                </a>
            </div>

            <div class="summary-grid mb-3">
                <div class="summary-card">
                    <div class="field-label mb-2">Required Documents</div>
                    <div class="summary-value">{{ $requiredCount }}</div>
                </div>
                <div class="summary-card">
                    <div class="field-label mb-2">Completed</div>
                    <div class="summary-value text-success" data-completed-count>{{ $completedRequiredCount }}</div>
                </div>
                <div class="summary-card">
                    <div class="field-label mb-2">Missing</div>
                    <div class="summary-value text-danger" data-missing-count>{{ $missingRequiredCount }}</div>
                </div>
                <div class="summary-card">
                    <div class="field-label mb-2">Internal References</div>
                    <div class="summary-value text-primary">{{ $internalReferenceCount }}</div>
                </div>
            </div>

            <div class="d-flex justify-content-between gap-3 flex-wrap mb-2">
                <div class="fw-semibold" data-progress-label>{{ $completedRequiredCount }} of {{ $requiredCount }} required documents completed</div>
                <div class="text-muted small">{{ $completionPercent }}%</div>
            </div>
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: {{ $completionPercent }}%;" aria-valuenow="{{ $completionPercent }}" aria-valuemin="0" aria-valuemax="100" data-progress-bar></div>
            </div>
        </div>

        @if ($isRevision)
            <div class="alert alert-warning border-0 mb-4">
                <div class="fw-semibold">Dokumen perlu revisi</div>
                <div>Perbaiki informasi dokumen dan upload file pengganti. File versi lama tetap tersimpan sebagai riwayat, sedangkan file baru menjadi versi terbaru.</div>
            </div>
        @endif

        <div class="card workflow-card section-card mb-4">
            <div class="card-header">
                <div>
                    <div class="section-kicker mb-1">Billing</div>
                    <h5 class="mb-0 fw-bold">Informasi Tagihan</h5>
                </div>
                <span class="badge bg-light text-dark border">Invoice Metadata</span>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Data ini akan digunakan untuk generate Lembar PPA, Lembar Verifikasi, compile dokumen, dan penomoran.</p>
                <div class="billing-grid">
                    <div class="billing-field">
                        <label class="form-label">Nomor Invoice</label>
                        <input type="text" class="form-control @error('invoice_number') is-invalid @enderror" name="invoice_number" value="{{ old('invoice_number', $transaction->invoiceMetadata?->invoice_number) }}" placeholder="Opsional, bisa diambil dari dokumen Invoice">
                        @error('invoice_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="billing-field">
                        <label class="form-label">Tanggal Invoice</label>
                        <input type="date" class="form-control @error('invoice_date') is-invalid @enderror" name="invoice_date" value="{{ old('invoice_date', optional($transaction->invoiceMetadata?->invoice_date)->format('Y-m-d')) }}">
                        @error('invoice_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="billing-field">
                        <label class="form-label">Tanggal Diterima</label>
                        <input type="date" class="form-control @error('received_date') is-invalid @enderror" name="received_date" value="{{ old('received_date', optional($transaction->invoiceMetadata?->received_date)->format('Y-m-d')) }}">
                        @error('received_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="billing-field">
                        <label class="form-label">Bank</label>
                        <input type="text" class="form-control @error('bank_name') is-invalid @enderror" name="bank_name" value="{{ old('bank_name', $transaction->invoiceMetadata?->bank_name ?? $transaction->vendor?->defaultBank?->name) }}">
                        @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="billing-field">
                        <label class="form-label">Nomor Rekening</label>
                        <input type="text" class="form-control @error('account_number') is-invalid @enderror" name="account_number" value="{{ old('account_number', $transaction->invoiceMetadata?->account_number ?? $transaction->vendor?->default_account_number) }}" inputmode="numeric" pattern="[0-9]{6,30}" maxlength="30" autocomplete="off" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        @error('account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="billing-field">
                        <label class="form-label">Atas Nama Rekening</label>
                        <input type="text" class="form-control @error('account_name') is-invalid @enderror" name="account_name" value="{{ old('account_name', $transaction->invoiceMetadata?->account_name) }}" placeholder="Nama pemilik rekening">
                        @error('account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="billing-field">
                        <label class="form-label">Nilai Invoice<span class="required-mark">*</span></label>
                        <input type="text" inputmode="numeric" class="form-control @error('invoice_value') is-invalid @enderror" name="invoice_value" value="{{ old('invoice_value', $transaction->invoiceMetadata?->invoice_value) }}" data-rupiah-input required>
                        @error('invoice_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="billing-field">
                        <label class="form-label">PPN</label>
                        <input type="text" inputmode="numeric" class="form-control @error('ppn_value') is-invalid @enderror" name="ppn_value" value="{{ old('ppn_value', $transaction->invoiceMetadata?->ppn_value) }}" data-rupiah-input>
                        @error('ppn_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="billing-field full">
                        <label class="form-label">Uraian Transaksi</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description', $transaction->invoiceMetadata?->description ?? $transaction->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card workflow-card section-card">
            <div class="card-header">
                <div>
                    <div class="section-kicker mb-1">Requirements</div>
                    <h5 class="mb-0 fw-bold">Document Requirements</h5>
                    <p class="text-muted mb-0 small">Lengkapi seluruh dokumen wajib sebelum mengirim dokumen PPA.</p>
                </div>
                <div class="document-toolbar">
                    <div class="status-filter" role="group" aria-label="Filter dokumen">
                        <button type="button" class="filter-pill is-active" data-document-filter="all">All</button>
                        <button type="button" class="filter-pill" data-document-filter="missing">Missing</button>
                        <button type="button" class="filter-pill" data-document-filter="uploaded">Uploaded</button>
                        <button type="button" class="filter-pill" data-document-filter="internal">Internal</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-expand-toggle>Expand All</button>
                </div>
            </div>
            <div class="card-body">
                <div class="accordion" id="{{ $documentAccordionId }}" data-document-list>
                    @forelse ($uploadableDocumentTypes as $index => $documentType)
                        @php
                            $usesInternalMemo = $documentType->code === 'PPA_MEMO_PERMOHONAN';
                            $usesInternalAgreement = $documentType->code === 'PPA_PERJANJIAN';
                            $isInternalReference = $usesInternalMemo || $usesInternalAgreement;
                            $internalModel = $usesInternalMemo ? $transaction->memoRequest : ($usesInternalAgreement ? $transaction->agreementReference : null);
                            $internalNumber = $usesInternalMemo ? $transaction->memoRequest?->memo_number : ($usesInternalAgreement ? $transaction->agreementReference?->contract_number : null);
                            $internalPreviewRoute = $usesInternalMemo && $transaction->memoRequest?->file_path
                                ? route('invoice-verification.master-data.memo-requests.preview', $transaction->memoRequest)
                                : ($usesInternalAgreement && $transaction->agreementReference?->file_path
                                    ? route('invoice-verification.master-data.agreement-references.preview', $transaction->agreementReference)
                                    : null);
                            $rejectedDocument = $rejectedDocuments->get($documentType->id);
                            $existingDocument = $transaction->latestDocuments->firstWhere('document_type_id', $documentType->id);
                            $documentNumber = old("documents.$documentType->id.document_information.document_number", data_get($rejectedDocument?->document_information_json, 'document_number', data_get($existingDocument?->document_information_json, 'document_number')));
                            $documentDate = old("documents.$documentType->id.document_information.document_date", data_get($rejectedDocument?->document_information_json, 'document_date', data_get($existingDocument?->document_information_json, 'document_date')));
                            $documentNotes = old("documents.$documentType->id.document_information.notes", data_get($rejectedDocument?->document_information_json, 'notes', data_get($existingDocument?->document_information_json, 'notes')));
                            $documentDomId = 'document-item-'.$documentType->id;
                            $collapseId = 'document-collapse-'.$documentType->id;
                            $isFirstError = $errors->has("documents.$documentType->id.file")
                                || $errors->has("documents.$documentType->id.document_information.document_number")
                                || $errors->has("documents.$documentType->id.document_information.document_date");
                            $isCompleted = $isInternalReference ? (bool) $internalPreviewRoute : (bool) $existingDocument;
                            $statusKey = $isInternalReference ? 'internal' : ($rejectedDocument ? 'missing' : ($isCompleted ? 'uploaded' : 'missing'));
                            $statusLabel = $isInternalReference ? ($internalPreviewRoute ? 'Available' : 'Not Available') : ($rejectedDocument ? 'Need Revision' : ($isCompleted ? 'Uploaded' : 'Missing'));
                            $statusClass = $isInternalReference
                                ? ($internalPreviewRoute ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary')
                                : ($rejectedDocument ? 'bg-warning-subtle text-warning' : ($isCompleted ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger'));
                            $requirementLabel = $isInternalReference ? 'Internal Reference' : ($documentType->is_required ? 'Required' : 'Optional');
                            $requirementClass = $isInternalReference ? 'bg-dark-subtle text-dark' : ($documentType->is_required ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary');
                            $sourceLabel = $isInternalReference ? 'Internal Reference' : str($documentType->source_type->value ?? $documentType->source_type)->replace('_', ' ')->title();
                            $shouldOpen = $loop->first || $isFirstError || (bool) $rejectedDocument;
                            $accountingRevisionNote = $rejectedDocument?->accountingVerificationItems
                                ->filter(fn ($item) => in_array($item->status?->value ?? $item->status, ['REVISION_REQUIRED', 'MISMATCH'], true))
                                ->sortByDesc('verified_at')
                                ->first()?->notes;
                            $revisionNote = $accountingRevisionNote
                                ?: $rejectedDocument?->vendorReview?->notes
                                ?: 'Dokumen perlu diperbaiki.';
                        @endphp
                        <div
                            class="accordion-item"
                            data-document-item
                            data-document-status="{{ $statusKey }}"
                            data-required="{{ (!$isInternalReference && $documentType->is_required) ? '1' : '0' }}"
                            data-complete="{{ $isCompleted ? '1' : '0' }}"
                            data-existing-complete="{{ (!$isInternalReference && $existingDocument && ! $rejectedDocument) ? '1' : '0' }}"
                            id="{{ $documentDomId }}"
                        >
                            <h2 class="accordion-header">
                                <button class="accordion-button {{ $shouldOpen ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $shouldOpen ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                                    <span class="document-index">{{ $loop->iteration }}</span>
                                    <span class="flex-grow-1">
                                        <span class="document-title d-block">{{ $documentType->name }}</span>
                                        <span class="document-meta d-block">
                                            {{ $sourceLabel }} · {{ $requirementLabel }} · {{ $statusLabel }}
                                            @if ($isInternalReference && $internalNumber)
                                                · {{ $internalNumber }}
                                            @elseif ($existingDocument?->file_name)
                                                · {{ $existingDocument->file_name }}
                                            @endif
                                        </span>
                                    </span>
                                    <span class="d-flex gap-2 flex-wrap justify-content-end">
                                        <span class="badge {{ $requirementClass }}">{{ $requirementLabel }}</span>
                                        <span class="badge {{ $statusClass }}" data-status-badge>{{ $statusLabel }}</span>
                                    </span>
                                </button>
                            </h2>
                            <div id="{{ $collapseId }}" class="accordion-collapse collapse {{ $shouldOpen ? 'show' : '' }}">
                                <div class="accordion-body">
                                    @if ($isInternalReference)
                                        <div class="internal-reference">
                                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                                <div>
                                                    <div class="field-label mb-1">Internal Reference</div>
                                                    <div class="fw-bold">{{ $documentType->name }}</div>
                                                    <div class="text-muted small mt-1">
                                                        Nomor: {{ $internalNumber ?? 'Dokumen referensi belum tersedia' }}
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                                    @if ($internalPreviewRoute)
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-primary d-inline-flex align-items-center gap-2 fw-semibold"
                                                            data-file-preview-url="{{ $internalPreviewRoute }}"
                                                            data-file-preview-title="{{ $documentType->name }} - {{ $internalNumber }}"
                                                        >
                                                            <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                                            <span>Lihat Dokumen</span>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <input type="hidden" name="documents[{{ $documentType->id }}][document_type_id]" value="{{ $documentType->id }}">
                                        @if ($rejectedDocument)
                                            <div class="alert alert-warning border-0">
                                                <div class="fw-semibold">Catatan Reject</div>
                                                <div>{{ $revisionNote }}</div>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-danger d-inline-flex align-items-center gap-2 fw-semibold mt-2"
                                                    data-file-preview-url="{{ route('invoice-verification.transaction-documents.preview', $rejectedDocument) }}"
                                                    data-file-preview-title="{{ $rejectedDocument->document_label ?: $rejectedDocument->documentType?->name }}"
                                                >
                                                    <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                                    <span>Lihat File Lama</span>
                                                </button>
                                            </div>
                                        @endif
                                        <div class="vendor-document-grid">
                                            <div data-required-group>
                                                <label class="form-label">Nomor Dokumen<span class="required-mark {{ $documentType->is_required ? '' : 'd-none' }}">*</span></label>
                                                <input
                                                    type="text"
                                                    class="form-control @error("documents.$documentType->id.document_information.document_number") is-invalid @enderror"
                                                    name="documents[{{ $documentType->id }}][document_information][document_number]"
                                                    value="{{ $documentNumber }}"
                                                    placeholder="Contoh: {{ str($documentType->code)->contains('INVOICE') ? 'INV-001/IV/2026' : 'BAPP-001/IV/2026' }}"
                                                    data-required-field="{{ $documentType->is_required ? '1' : '0' }}"
                                                >
                                                @error("documents.$documentType->id.document_information.document_number")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                <div class="validation-message">Nomor dokumen wajib diisi.</div>
                                            </div>
                                            <div data-required-group>
                                                <label class="form-label">Tanggal Dokumen<span class="required-mark {{ $documentType->is_required ? '' : 'd-none' }}">*</span></label>
                                                <input
                                                    type="date"
                                                    class="form-control @error("documents.$documentType->id.document_information.document_date") is-invalid @enderror"
                                                    name="documents[{{ $documentType->id }}][document_information][document_date]"
                                                    value="{{ $documentDate }}"
                                                    data-required-field="{{ $documentType->is_required ? '1' : '0' }}"
                                                >
                                                @error("documents.$documentType->id.document_information.document_date")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                <div class="validation-message">Tanggal dokumen wajib diisi.</div>
                                            </div>
                                            <div class="full">
                                                <label class="form-label">Keterangan Dokumen</label>
                                                <textarea
                                                    class="form-control @error("documents.$documentType->id.document_information.notes") is-invalid @enderror"
                                                    rows="2"
                                                    name="documents[{{ $documentType->id }}][document_information][notes]"
                                                    placeholder="Informasi tambahan terkait dokumen ini"
                                                >{{ $documentNotes }}</textarea>
                                                @error("documents.$documentType->id.document_information.notes")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="full" data-required-group>
                                                <label class="form-label">File Dokumen<span class="required-mark {{ $documentType->is_required ? '' : 'd-none' }}">*</span></label>
                                                <div class="upload-box @if($existingDocument?->file_name) has-file @endif" data-upload-box>
                                                    <input
                                                        type="file"
                                                        class="upload-input @error("documents.$documentType->id.file") is-invalid @enderror"
                                                        name="documents[{{ $documentType->id }}][file]"
                                                        id="file-{{ $documentType->id }}"
                                                        data-file-input
                                                        data-required-field="{{ $documentType->is_required ? '1' : '0' }}"
                                                        data-existing-name="{{ $existingDocument?->file_name }}"
                                                        data-allowed-extensions="{{ $allowedExtensions->join(',') }}"
                                                        data-max-size="{{ $maxUploadBytes }}"
                                                        accept="{{ $allowedExtensions->map(fn ($extension) => '.'.$extension)->join(',') }}"
                                                    >
                                                    <div class="d-flex align-items-start gap-3 flex-wrap">
                                                        <span class="upload-icon"><iconify-icon icon="solar:upload-outline" class="fs-22"></iconify-icon></span>
                                                        <div class="flex-grow-1">
                                                            <div class="fw-semibold">Drag & drop file di sini atau klik untuk memilih file</div>
                                                            <div class="text-muted small mt-1">{{ $allowedMimes }} maksimal {{ $maxUploadMb }}MB</div>
                                                        </div>
                                                        <label for="file-{{ $documentType->id }}" class="btn btn-sm btn-outline-primary mb-0">Pilih File</label>
                                                    </div>
                                                    <div class="file-state align-items-center justify-content-between gap-3 flex-wrap" data-file-state>
                                                        <div>
                                                            <div class="fw-semibold" data-file-name>{{ $existingDocument?->file_name ?? 'Belum ada file dipilih' }}</div>
                                                            <div class="text-muted small" data-file-size>{{ $existingDocument?->file_name ? 'File existing' : 'Missing' }}</div>
                                                        </div>
                                                        <div class="d-flex gap-2 flex-wrap">
                                                            @if ($existingDocument)
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-outline-primary d-inline-flex align-items-center gap-2 fw-semibold"
                                                                    data-file-preview-url="{{ route('invoice-verification.transaction-documents.preview', $existingDocument) }}"
                                                                    data-file-preview-title="{{ $existingDocument->document_label ?: $existingDocument->documentType?->name }}"
                                                                    data-existing-preview
                                                                >
                                                                    <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                                                    <span>Lihat Dokumen</span>
                                                                </button>
                                                            @endif
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-remove-file>Remove</button>
                                                            <span class="badge bg-info-subtle text-info">Uploaded</span>
                                                        </div>
                                                    </div>
                                                    @error("documents.$documentType->id.file")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                    <div class="validation-message">File dokumen wajib diupload.</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4 border rounded-3">
                            Tidak ada dokumen yang perlu diupload ulang.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="sticky-submit-bar">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <div class="fw-semibold" data-sticky-summary>
                        {{ $missingRequiredCount > 0 ? $missingRequiredCount.' required documents missing' : 'All required documents completed' }}
                    </div>
                    <div class="text-muted small">Memo dan kontrak yang sudah terdaftar akan dipakai otomatis sebagai referensi internal.</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary" data-save-draft>Simpan Draft</button>
                    <button class="btn btn-primary" type="submit">Upload Dokumen PPA</button>
                </div>
            </div>
        </div>
    </form>
</div>

@include('invoice-verification.components.file-preview-modal')

@endsection

@section('scripts')
@include('invoice-verification.partials.rupiah-input')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-ppa-upload-form]');
        const documentItems = Array.from(document.querySelectorAll('[data-document-item]'));
        const completedCount = document.querySelector('[data-completed-count]');
        const missingCount = document.querySelector('[data-missing-count]');
        const progressLabel = document.querySelector('[data-progress-label]');
        const progressBar = document.querySelector('[data-progress-bar]');
        const stickySummary = document.querySelector('[data-sticky-summary]');
        const requiredTotal = documentItems.filter((item) => item.dataset.required === '1').length;
        let expanded = false;

        const formatBytes = (bytes) => {
            if (!bytes) return '0 KB';
            const units = ['B', 'KB', 'MB', 'GB'];
            const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
            return `${(bytes / Math.pow(1024, index)).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
        };

        const getItemComplete = (item) => {
            if (item.dataset.required !== '1') {
                return item.dataset.complete === '1';
            }

            const fileInput = item.querySelector('[data-file-input]');
            return item.dataset.complete === '1' || !!fileInput?.files?.length;
        };

        const setExistingItemSubmission = (item, enabled) => {
            if (item.dataset.existingComplete !== '1') return;

            setDocumentFieldsEnabled(item, enabled);
        };

        const setDocumentFieldsEnabled = (item, enabled) => {
            item.querySelectorAll('input[name^="documents["], textarea[name^="documents["], select[name^="documents["]').forEach((field) => {
                if (field.matches('[data-file-input]')) return;
                field.disabled = !enabled;
            });
        };

        const setStatusBadge = (item, label, className, statusKey) => {
            const badge = item?.querySelector('[data-status-badge]');
            if (!badge) return;
            badge.textContent = label;
            badge.className = `badge ${className}`;
            if (statusKey) item.dataset.documentStatus = statusKey;
        };

        const updateProgress = () => {
            const complete = documentItems
                .filter((item) => item.dataset.required === '1')
                .filter(getItemComplete)
                .length;
            const missing = Math.max(requiredTotal - complete, 0);
            const percent = requiredTotal > 0 ? Math.round((complete / requiredTotal) * 100) : 100;

            if (completedCount) completedCount.textContent = complete;
            if (missingCount) missingCount.textContent = missing;
            if (progressLabel) progressLabel.textContent = `${complete} of ${requiredTotal} required documents completed`;
            if (progressBar) {
                progressBar.style.width = `${percent}%`;
                progressBar.setAttribute('aria-valuenow', percent);
            }
            if (stickySummary) {
                stickySummary.textContent = missing > 0 ? `${missing} required documents missing` : 'All required documents completed';
            }
        };

        document.querySelectorAll('[data-upload-box]').forEach((box) => {
            const input = box.querySelector('[data-file-input]');
            const fileName = box.querySelector('[data-file-name]');
            const fileSize = box.querySelector('[data-file-size]');
            const removeButton = box.querySelector('[data-remove-file]');

            const applyFile = () => {
                const file = input?.files?.[0];
                if (!file) return;
                const allowedExtensions = (input.dataset.allowedExtensions || '').split(',').filter(Boolean);
                const maxSize = Number(input.dataset.maxSize || 0);
                const extension = file.name.split('.').pop().toLowerCase();
                const validationMessage = box.querySelector('.validation-message');

                if ((allowedExtensions.length && !allowedExtensions.includes(extension)) || (maxSize && file.size > maxSize)) {
                    input.value = '';
                    box.classList.add('is-invalid-local');
                    if (validationMessage) {
                        validationMessage.textContent = maxSize && file.size > maxSize
                            ? 'Ukuran file terlalu besar.'
                            : 'Format file tidak valid.';
                    }
                    return;
                }

                box.classList.remove('is-invalid-local');
                if (validationMessage) validationMessage.textContent = 'File dokumen wajib diupload.';

                box.classList.add('has-file');
                const item = box.closest('[data-document-item]');
                if (item) {
                    item.setAttribute('data-complete', '1');
                    setDocumentFieldsEnabled(item, true);
                    setStatusBadge(item, 'Uploaded', 'bg-info-subtle text-info', 'uploaded');
                }
                if (fileName) fileName.textContent = file.name;
                if (fileSize) fileSize.textContent = formatBytes(file.size);
                updateProgress();
            };

            box.addEventListener('click', (event) => {
                if (event.target.closest('button') || event.target.closest('label')) return;
                input?.click();
            });

            box.addEventListener('dragover', (event) => {
                event.preventDefault();
                box.classList.add('is-dragover');
            });

            box.addEventListener('dragleave', () => box.classList.remove('is-dragover'));

            box.addEventListener('drop', (event) => {
                event.preventDefault();
                box.classList.remove('is-dragover');
                if (!input || !event.dataTransfer.files.length) return;
                input.files = event.dataTransfer.files;
                applyFile();
            });

            input?.addEventListener('change', applyFile);

            removeButton?.addEventListener('click', () => {
                if (input) input.value = '';
                box.classList.remove('has-file');
                const item = box.closest('[data-document-item]');
                if (item) {
                    item.setAttribute('data-complete', item.dataset.existingComplete === '1' ? '1' : '0');
                    setExistingItemSubmission(item, false);
                }
                if (item?.dataset.existingComplete === '1') {
                    box.classList.add('has-file');
                    setStatusBadge(item, 'Uploaded', 'bg-info-subtle text-info', 'uploaded');
                    if (fileName) fileName.textContent = input?.dataset.existingName || 'File existing';
                    if (fileSize) fileSize.textContent = 'File existing';
                } else {
                    if (item) setStatusBadge(item, 'Missing', 'bg-danger-subtle text-danger', 'missing');
                    if (fileName) fileName.textContent = 'Belum ada file dipilih';
                    if (fileSize) fileSize.textContent = 'Missing';
                }
                updateProgress();
            });
        });

        documentItems.forEach((item) => setExistingItemSubmission(item, false));

        document.querySelectorAll('[data-document-filter]').forEach((button) => {
            button.addEventListener('click', () => {
                const filter = button.dataset.documentFilter;
                document.querySelectorAll('[data-document-filter]').forEach((item) => item.classList.toggle('is-active', item === button));
                documentItems.forEach((item) => {
                    const show = filter === 'all'
                        || item.dataset.documentStatus === filter
                        || (filter === 'uploaded' && getItemComplete(item));
                    item.classList.toggle('d-none', !show);
                });
            });
        });

        document.querySelector('[data-expand-toggle]')?.addEventListener('click', function () {
            expanded = !expanded;
            document.querySelectorAll('.accordion-collapse').forEach((collapse) => {
                bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false })[expanded ? 'show' : 'hide']();
            });
            this.textContent = expanded ? 'Collapse All' : 'Expand All';
        });

        document.querySelector('[data-save-draft]')?.addEventListener('click', function () {
            const key = `signal-ppa-draft-${form?.action || location.pathname}`;
            const payload = new FormData(form);
            const data = {};
            payload.forEach((value, keyName) => {
                if (value instanceof File) return;
                data[keyName] = value;
            });
            localStorage.setItem(key, JSON.stringify(data));
            this.textContent = 'Draft Tersimpan';
            window.setTimeout(() => { this.textContent = 'Simpan Draft'; }, 1600);
        });

        form?.addEventListener('submit', function (event) {
            let firstInvalidItem = null;

            documentItems.forEach((item) => {
                const optionalFileInput = item.querySelector('[data-file-input]');
                if (item.dataset.required !== '1' && !optionalFileInput?.files?.length && item.dataset.existingComplete !== '1') {
                    setDocumentFieldsEnabled(item, false);
                    return;
                }

                if (item.dataset.required !== '1') return;
                const itemFileInput = item.querySelector('[data-file-input]');
                if (item.dataset.existingComplete === '1' && !itemFileInput?.files?.length) return;

                item.querySelectorAll('[data-required-group]').forEach((group) => {
                    const field = group.querySelector('[data-required-field="1"]');
                    const isFile = field?.matches('input[type="file"]');
                    const hasValue = isFile ? (item.dataset.complete === '1' || !!field?.files?.length) : !!field?.value?.trim();

                    group.classList.toggle('is-invalid-local', !hasValue);
                    if (!hasValue && !firstInvalidItem) {
                        firstInvalidItem = item;
                    }
                });
            });

            if (firstInvalidItem) {
                event.preventDefault();
                const collapse = firstInvalidItem.querySelector('.accordion-collapse');
                if (collapse) {
                    bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
                }
                firstInvalidItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        @if ($errors->any())
            const firstServerError = document.querySelector('.is-invalid, .invalid-feedback.d-block');
            const serverErrorItem = firstServerError?.closest('[data-document-item]');
            if (serverErrorItem) {
                const collapse = serverErrorItem.querySelector('.accordion-collapse');
                if (collapse) bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
                window.setTimeout(() => serverErrorItem.scrollIntoView({ behavior: 'smooth', block: 'center' }), 150);
            }
        @endif

        updateProgress();
    });
</script>
@endsection
