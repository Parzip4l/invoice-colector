<div class="card workflow-card section-card">
    <div class="card-header">
        <div>
            <div class="section-kicker mb-1">Activity</div>
            <h5 class="section-title">Timeline</h5>
        </div>
    </div>
    <div class="card-body">
        @if ($histories->isNotEmpty())
            <div class="activity-list">
                @foreach ($histories as $history)
                    <div class="activity-item">
                        <span class="activity-node">
                            <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                        </span>
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
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <span class="empty-icon mb-3"><iconify-icon icon="solar:clock-circle-outline"></iconify-icon></span>
                <div class="fw-semibold">Belum ada histori status.</div>
                <div class="text-muted small mt-1">Aktivitas workflow akan muncul setelah transaksi bergerak ke tahap berikutnya.</div>
            </div>
        @endif
    </div>
</div>
