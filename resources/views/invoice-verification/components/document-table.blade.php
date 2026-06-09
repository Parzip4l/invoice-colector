<div class="table-responsive">
    <table class="table table-centered table-nowrap mb-0">
        <thead class="table-light">
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
            @forelse ($documents as $document)
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
                            <div class="mt-1">
                                <a href="{{ route('invoice-verification.transaction-documents.preview', $document) }}" target="_blank" rel="noopener" class="btn btn-sm btn-link px-0">Preview File</a>
                            </div>
                        @endcan
                    </td>
                    <td>{{ str($document->source_actor->value ?? $document->source_actor)->replace('_', ' ')->title() }}</td>
                    <td>v{{ $document->version }}</td>
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
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada dokumen.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
