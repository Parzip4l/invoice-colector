@extends('layouts.vertical', ['subtitle' => 'Generated Document Detail'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Generated Documents', 'subtitle' => 'Detail'])
@include('invoice-verification.partials.flash')

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <div>
                        <h4 class="mb-1">{{ str($generatedDocument->document_code)->replace('_', ' ')->title() }}</h4>
                        <p class="text-muted mb-0">{{ $generatedDocument->transaction?->registration_number }}</p>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold">{{ $generatedDocument->document_number ?? '-' }}</div>
                        <div class="text-muted small">Generated {{ $generatedDocument->generated_at?->format('d M Y H:i') }}</div>
                    </div>
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Generation Status</small>
                            <div class="fw-semibold">{{ $generatedDocument->generation_status->value }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Approval Mode</small>
                            <div class="fw-semibold">{{ $generatedDocument->approval_mode->value }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Template</small>
                            <div class="fw-semibold">{{ $generatedDocument->templateReference?->name ?? 'Default placeholder' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Approval Steps</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-centered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Step</th>
                                <th>Approver</th>
                                <th>Status</th>
                                <th>Action At</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($generatedDocument->approvals as $approval)
                                <tr>
                                    <td>{{ $approval->approvalFlow?->step_name }}</td>
                                    <td>{{ $approval->approver?->name }}</td>
                                    <td>@include('invoice-verification.components.status-badge', ['value' => $approval->status])</td>
                                    <td>{{ $approval->action_at?->format('d M Y H:i') ?? '-' }}</td>
                                    <td>{{ $approval->notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada alur approval.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
