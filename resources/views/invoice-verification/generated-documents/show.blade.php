@extends('layouts.vertical', ['subtitle' => 'Generated Document Detail'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Generated Documents', 'subtitle' => 'Detail'])
@include('invoice-verification.partials.flash')

@php
    $documentTitle = str($generatedDocument->document_code)->replace('_', ' ')->title();
    $transaction = $generatedDocument->transaction;
@endphp

<style>
    .generated-detail-page {
        --signal-primary: #e21a1a;
        --signal-primary-dark: #b91515;
        --signal-border: rgba(31, 41, 55, .09);
        --signal-muted: #6b7280;
        --signal-heading: #172033;
        --signal-shadow: 0 14px 34px rgba(27, 36, 54, .08);
        color: var(--signal-heading);
    }

    .generated-detail-page .workflow-card {
        border: 1px solid var(--signal-border);
        border-radius: 16px;
        background: #ffffff;
        box-shadow: var(--signal-shadow);
    }

    .generated-detail-page .document-hero {
        padding: 22px 24px;
        background: linear-gradient(135deg, rgba(226, 26, 26, .08), rgba(255, 255, 255, .96) 52%, rgba(22, 163, 74, .07));
    }

    .generated-detail-page .section-card .card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding: 18px 20px 12px;
        border-color: var(--signal-border);
        background: transparent;
    }

    .generated-detail-page .section-card .card-body {
        padding: 18px 20px 20px;
    }

    .generated-detail-page .section-kicker,
    .generated-detail-page .detail-label {
        color: var(--signal-muted);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .generated-detail-page .document-title {
        margin: 0;
        font-size: 1.45rem;
        font-weight: 750;
        letter-spacing: 0;
    }

    .generated-detail-page .document-subtitle {
        color: var(--signal-muted);
        font-size: .92rem;
    }

    .generated-detail-page .btn {
        border-radius: 10px;
        font-weight: 650;
    }

    .generated-detail-page .btn-primary {
        border-color: var(--signal-primary);
        background: var(--signal-primary);
        box-shadow: 0 8px 18px rgba(226, 26, 26, .16);
    }

    .generated-detail-page .btn-primary:hover {
        border-color: var(--signal-primary-dark);
        background: var(--signal-primary-dark);
    }

    .generated-detail-page .btn-outline-primary {
        border-color: rgba(226, 26, 26, .32);
        color: var(--signal-primary);
    }

    .generated-detail-page .btn-outline-primary:hover {
        border-color: var(--signal-primary);
        background: var(--signal-primary);
        color: #ffffff;
    }

    .generated-detail-page .badge {
        border-radius: 999px;
        padding: .42rem .68rem;
        font-weight: 700;
    }

    .generated-detail-page .info-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .generated-detail-page .info-item,
    .generated-detail-page .summary-box {
        border: 1px solid var(--signal-border);
        border-radius: 14px;
        background: #fbfcfe;
        padding: 14px;
    }

    .generated-detail-page .info-icon,
    .generated-detail-page .empty-icon {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: rgba(226, 26, 26, .09);
        color: var(--signal-primary);
    }

    .generated-detail-page .detail-value {
        color: var(--signal-heading);
        font-weight: 750;
        line-height: 1.35;
        word-break: break-word;
    }

    .generated-detail-page .modern-table thead th {
        border-top: 0;
        border-bottom: 1px solid var(--signal-border);
        background: #f9fafb;
        color: var(--signal-muted);
        font-size: .72rem;
        font-weight: 750;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .generated-detail-page .modern-table tbody td {
        padding-top: 14px;
        padding-bottom: 14px;
        border-color: rgba(31, 41, 55, .07);
        vertical-align: middle;
    }

    .generated-detail-page .empty-state {
        border: 1px dashed rgba(107, 114, 128, .28);
        border-radius: 14px;
        background: #fbfcfe;
        padding: 24px;
        text-align: center;
    }

    @media (max-width: 1199.98px) {
        .generated-detail-page .info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .generated-detail-page .document-hero,
        .generated-detail-page .section-card .card-header,
        .generated-detail-page .section-card .card-body {
            padding-left: 16px;
            padding-right: 16px;
        }

        .generated-detail-page .info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="generated-detail-page">
    <div class="workflow-card document-hero mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div class="flex-grow-1">
                <div class="section-kicker mb-2">Generated Document</div>
                <h3 class="document-title">{{ $documentTitle }}</h3>
                <div class="document-subtitle mt-1">
                    {{ $transaction?->registration_number ?? '-' }} · {{ $transaction?->title ?? 'Transaction document' }}
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                    <span class="badge bg-info-subtle text-info">{{ str($generatedDocument->generation_status?->value ?? '-')->replace('_', ' ')->title() }}</span>
                    <span class="badge bg-light text-dark border">{{ $generatedDocument->file_name ?? 'Dokumen sistem' }}</span>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 flex-wrap">
                @if ($generatedDocument->file_path)
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-file-preview-url="{{ route('invoice-verification.generated-documents.preview', $generatedDocument) }}"
                        data-file-preview-title="{{ $documentTitle }} - {{ $generatedDocument->document_number ?? $transaction?->registration_number }}"
                    >
                        <iconify-icon icon="solar:eye-outline" class="me-1"></iconify-icon>Preview Dokumen
                    </button>
                @endif
                @if ($transaction)
                    <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn btn-outline-secondary">
                        <iconify-icon icon="solar:arrow-left-outline" class="me-1"></iconify-icon>Kembali ke Transaksi
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card workflow-card section-card mb-3">
                <div class="card-header">
                    <div>
                        <div class="section-kicker mb-1">Overview</div>
                        <h5 class="mb-0 fw-bold">Document Summary</h5>
                    </div>
                    <span class="badge bg-light text-dark border">{{ $generatedDocument->document_number ?? 'Belum bernomor' }}</span>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-icon mb-3"><iconify-icon icon="solar:document-text-outline"></iconify-icon></span>
                            <div class="detail-label">Nomor Dokumen</div>
                            <div class="detail-value mt-1">{{ $generatedDocument->document_number ?? '-' }}</div>
                        </div>
                        <div class="info-item">
                            <span class="info-icon mb-3"><iconify-icon icon="solar:check-circle-outline"></iconify-icon></span>
                            <div class="detail-label">Generation Status</div>
                            <div class="detail-value mt-1">{{ str($generatedDocument->generation_status?->value ?? '-')->replace('_', ' ')->title() }}</div>
                        </div>
                        <div class="info-item">
                            <span class="info-icon mb-3"><iconify-icon icon="solar:file-text-outline"></iconify-icon></span>
                            <div class="detail-label">Nama File</div>
                            <div class="detail-value mt-1">{{ $generatedDocument->file_name ?? '-' }}</div>
                        </div>
                        <div class="info-item">
                            <span class="info-icon mb-3"><iconify-icon icon="solar:calendar-date-outline"></iconify-icon></span>
                            <div class="detail-label">Generated At</div>
                            <div class="detail-value mt-1">{{ $generatedDocument->generated_at?->format('d M Y H:i') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card workflow-card section-card mb-3">
                <div class="card-header">
                    <div>
                        <div class="section-kicker mb-1">Template</div>
                        <h5 class="mb-0 fw-bold">Source Template</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="summary-box">
                        <div class="detail-label">Template Name</div>
                        <div class="detail-value mt-1">{{ $generatedDocument->templateReference?->name ?? 'Default placeholder' }}</div>
                        <div class="text-muted small mt-2">{{ $generatedDocument->templateReference?->code ?? 'SIGNAL generated template' }}</div>
                    </div>
                </div>
            </div>

            <div class="card workflow-card section-card">
                <div class="card-header">
                    <div>
                        <div class="section-kicker mb-1">Transaction</div>
                        <h5 class="mb-0 fw-bold">Related Transaction</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="summary-box">
                        <div class="detail-label">Registration Number</div>
                        <div class="detail-value mt-1">{{ $transaction?->registration_number ?? '-' }}</div>
                        <div class="text-muted small mt-2">{{ $transaction?->vendor?->name ?? $transaction?->title ?? '-' }}</div>
                        @if ($transaction)
                            <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary mt-3">Lihat Transaction Detail</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('invoice-verification.components.file-preview-modal')
@endsection
