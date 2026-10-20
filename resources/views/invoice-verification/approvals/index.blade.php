@extends('layouts.vertical', ['subtitle' => 'Inbox Approval'])

@section('css')
@include('invoice-verification.partials.table-ui')
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Invoice Verification', 'subtitle' => 'Inbox Approval'])
@include('invoice-verification.partials.flash')

<div class="card iv-table-card">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Inbox Approval</h5>
                <p class="text-muted mb-0">Daftar persetujuan transaksi tagihan yang membutuhkan tindakan Anda.</p>
            </div>
        </div>

        <form method="GET" class="iv-table-toolbar" style="--iv-filter-count: 1;">
            <div>
                <label class="form-label">Cari Transaksi</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><iconify-icon icon="solar:magnifer-outline" class="fs-18"></iconify-icon></span>
                    <input type="search" class="form-control" name="search" value="{{ $search }}" placeholder="No. Registrasi, judul, vendor, pemohon...">
                </div>
            </div>
            <div>
                <label class="form-label">Status Approval</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="PENDING" @selected($status === 'PENDING')>Pending (Menunggu)</option>
                    <option value="APPROVED" @selected($status === 'APPROVED')>Approved (Disetujui)</option>
                    <option value="REJECTED" @selected($status === 'REJECTED')>Rejected (Ditolak)</option>
                </select>
            </div>
            <div class="iv-table-actions">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="solar:filter-outline" class="fs-18"></iconify-icon>
                    <span>Filter</span>
                </button>
                <a href="{{ route('invoice-verification.approvals.index') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>No. Registrasi</th>
                        <th>Transaksi</th>
                        <th>Tahap Approval</th>
                        <th>Pemohon</th>
                        <th>Nilai Tagihan</th>
                        <th>Status</th>
                        <th>Catatan / Tanggal</th>
                        <th class="text-end" style="width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($approvals as $index => $approval)
                        @php
                            $transaction = $approval->transaction;
                            $stepNo = (int) ($approval->approvalFlow?->step_no ?? 1);
                            $canProcess = auth()->user()?->can('process', $approval);
                            $isPending = $approval->status === \App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus::PENDING;

                            $kadepApproved = true;
                            if ($stepNo === 2) {
                                $kadepApproval = $transaction->approvalTransactions->first(fn ($item) => $item->approvalFlow?->step_no == 1);
                                $kadepApproved = $kadepApproval && $kadepApproval->status === \App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus::APPROVED;
                            }
                        @endphp
                        <tr>
                            <td>{{ $approvals->firstItem() + $index }}</td>
                            <td>
                                <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="fw-semibold text-primary">
                                    {{ $transaction->registration_number }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $transaction->title }}</div>
                                <small class="text-muted">{{ $transaction->transactionType?->name }} &bull; {{ $transaction->vendor?->name ?? 'Internal' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary">
                                    {{ $approval->approvalFlow?->step_name ?? 'Step ' . $stepNo }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $transaction->owner?->name ?? '-' }}</div>
                                <small class="text-muted">{{ $transaction->department?->name ?? '-' }}</small>
                            </td>
                            <td>
                                Rp {{ number_format($transaction->invoiceMetadata?->invoice_value ?? $transaction->contract_value ?? 0, 0, ',', '.') }}
                            </td>
                            <td>
                                <span class="badge {{ $approval->status->badgeClass() }}">
                                    {{ $approval->status->value }}
                                </span>
                                @if($approval->portal_signature_url || $approval->portal_qr_url)
                                    <div class="mt-1">
                                        <a href="{{ $approval->portal_signature_url ?: $approval->portal_qr_url }}" target="_blank" rel="noopener noreferrer" class="badge bg-light text-success border text-decoration-none d-inline-flex align-items-center gap-1" title="Verifikasi Tanda Tangan Portal">
                                            <iconify-icon icon="solar:qr-code-outline"></iconify-icon>
                                            <span>QR Portal</span>
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($approval->action_at)
                                    <div class="small text-muted">{{ $approval->action_at->translatedFormat('d M Y H:i') }}</div>
                                @endif
                                @if($approval->notes)
                                    <div class="small text-dark text-truncate" style="max-width: 180px;" title="{{ $approval->notes }}">
                                        {{ $approval->notes }}
                                    </div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-secondary" title="Lihat Detail">
                                        <iconify-icon icon="solar:eye-outline"></iconify-icon>
                                    </a>

                                    @if($isPending && $canProcess)
                                        @if($stepNo === 2 && ! $kadepApproved)
                                            <button type="button" class="btn btn-sm btn-secondary disabled" title="Menunggu approval Kadep">
                                                <iconify-icon icon="solar:lock-outline"></iconify-icon>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal-{{ $approval->id }}" title="Approve">
                                                <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $approval->id }}" title="Reject">
                                                <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                                            </button>
                                        @endif
                                    @endif
                                </div>

                                @if($isPending && $canProcess && ($stepNo !== 2 || $kadepApproved))
                                    <!-- Modal Approve -->
                                    <div class="modal fade text-start" id="approveModal-{{ $approval->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('invoice-verification.approvals.update', $approval) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status" value="APPROVED">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Konfirmasi Persetujuan</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Apakah Anda yakin menyetujui transaksi <strong>{{ $transaction->registration_number }}</strong> ({{ $approval->approvalFlow?->step_name }})?</p>
                                                        <div class="mb-3">
                                                            <label class="form-label">Catatan (Opsional)</label>
                                                            <textarea name="notes" class="form-control" rows="3" placeholder="Masukkan catatan jika ada..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-success">Setujui (Approve)</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Reject -->
                                    <div class="modal fade text-start" id="rejectModal-{{ $approval->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('invoice-verification.approvals.update', $approval) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status" value="REJECTED">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title text-danger">Tolak Transaksi</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Transaksi <strong>{{ $transaction->registration_number }}</strong> akan dikembalikan kepada pemohon untuk revisi dan tidak dapat masuk ke Accounting.</p>
                                                        <div class="mb-3">
                                                            <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                                                            <textarea name="notes" class="form-control" rows="3" required placeholder="Jelaskan alasan penolakan atau revisi yang harus dilakukan pemohon..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger">Tolak (Reject)</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                Tidak ada data approval yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($approvals->hasPages())
        <div class="card-footer bg-white border-top">
            {{ $approvals->links() }}
        </div>
    @endif
</div>
@endsection
