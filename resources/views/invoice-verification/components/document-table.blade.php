@php
    $tableClass = isset($transaction) ? 'modern-table' : '';
@endphp

@once
    <style>
        .document-preview-action {
            min-height: 38px;
            padding: .45rem .8rem;
            font-weight: 700;
            border-width: 1.5px;
            box-shadow: 0 6px 16px rgba(226, 26, 26, .08);
        }

        .document-preview-action iconify-icon {
            font-size: 18px;
        }

        .document-table-actions {
            margin-top: .75rem;
        }
    </style>
@endonce

@if ($documents->isNotEmpty())
<div class="table-responsive">
    <table class="table table-centered table-nowrap mb-0 {{ $tableClass }}">
        <thead class="{{ isset($transaction) ? '' : 'table-light' }}">
            <tr>
                <th>Dokumen</th>
                <th>Sumber</th>
                <th>Versi</th>
                <th>Status</th>
                <th>Review / Catatan</th>
                <th>Uploaded</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($documents as $document)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $document->document_label ?: $document->documentType?->name }}</div>
                        <div class="text-muted small">{{ $document->file_name }}</div>
                        @if ($document->document_information_json)
                            <div class="text-muted small mt-1">
                                {{ $document->document_information_json['document_number'] ?? '-' }}
                                @if (!empty($document->document_information_json['document_date']))
                                    · {{ \Illuminate\Support\Carbon::parse($document->document_information_json['document_date'])->format('d M Y') }}
                                @endif
                            </div>
                            @if (!empty($document->document_information_json['notes']))
                                <div class="text-muted small">{{ $document->document_information_json['notes'] }}</div>
                            @endif
                        @endif
                        @can('view', $document)
                            <div class="document-table-actions">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary document-preview-action d-inline-flex align-items-center gap-2"
                                    data-file-preview-url="{{ route('invoice-verification.transaction-documents.preview', $document) }}"
                                    data-file-preview-title="{{ $document->document_label ?: $document->documentType?->name }}"
                                >
                                    <iconify-icon icon="solar:eye-outline"></iconify-icon>
                                    <span>Lihat Dokumen</span>
                                </button>
                            </div>
                        @endcan
                    </td>
                    <td>{{ str($document->source_actor->value ?? $document->source_actor)->replace('_', ' ')->title() }}</td>
                    <td><span class="badge bg-light text-dark border">v{{ $document->version }}</span></td>
                    <td>@include('invoice-verification.components.status-badge', ['value' => $document->status])</td>
                    <td>
                        @if ($document->vendorReview)
                            <div class="fw-semibold">{{ str($document->vendorReview->status->value)->replace('_', ' ')->title() }}</div>
                            <div class="text-muted small">{{ $document->vendorReview->notes ?: '-' }}</div>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $document->uploaded_at?->format('d M Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
    <div class="{{ isset($transaction) ? 'empty-state' : 'text-center text-muted py-4' }}">
        @if (isset($transaction))
            <span class="empty-icon mb-3"><iconify-icon icon="solar:folder-open-outline"></iconify-icon></span>
            <div class="fw-semibold">Belum ada dokumen.</div>
            <div class="text-muted small mt-1">Upload dokumen transaksi agar admin dapat melakukan review kelengkapan.</div>
            @can('uploadDocuments', $transaction)
                <a href="{{ route('invoice-verification.transactions.documents.show', $transaction) }}" class="btn btn-sm btn-outline-primary mt-3">Upload Dokumen</a>
            @endcan
        @else
            Belum ada dokumen.
        @endif
    </div>
@endif
