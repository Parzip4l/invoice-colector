<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Timeline</h5>
    </div>
    <div class="card-body">
        @forelse ($histories as $history)
            <div class="d-flex gap-3 {{ !$loop->last ? 'pb-3 mb-3 border-bottom' : '' }}">
                <div>
                    <div class="avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                        <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between gap-3 flex-wrap">
                        <div>
                            <h6 class="mb-1">{{ str($history->to_status)->replace('_', ' ')->title() }}</h6>
                            <p class="text-muted mb-1">Step: {{ str($history->to_step)->replace('_', ' ')->title() }}</p>
                        </div>
                        <small class="text-muted">{{ $history->created_at?->format('d M Y H:i') }}</small>
                    </div>
                    @if ($history->notes)
                        <p class="mb-1">{{ $history->notes }}</p>
                    @endif
                    <small class="text-muted">Oleh: {{ $history->changer?->name ?? 'System' }}</small>
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">Belum ada histori perubahan status.</p>
        @endforelse
    </div>
</div>
