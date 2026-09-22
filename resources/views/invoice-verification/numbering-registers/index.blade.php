@extends('layouts.vertical', ['subtitle' => 'Numbering Register'])

@section('css')
@include('invoice-verification.partials.table-ui')
<style>
    .numbering-filter {
        display: grid;
        gap: 1.1rem;
    }

    .numbering-filter__main,
    .numbering-filter__advanced {
        display: grid;
        gap: .9rem;
        align-items: end;
    }

    .numbering-filter__main {
        grid-template-columns: minmax(280px, 1.4fr) minmax(190px, .8fr) minmax(230px, 1fr);
    }

    .numbering-filter__advanced {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .numbering-filter .form-label {
        color: #64748b;
        font-size: .75rem;
        font-weight: 700;
        margin-bottom: .3rem;
    }

    .numbering-filter__range {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 34px minmax(0, 1fr);
        align-items: center;
        gap: .5rem;
    }

    .numbering-filter__range-separator {
        align-items: center;
        background: #f1f5f9;
        border-radius: 999px;
        color: #94a3b8;
        display: inline-flex;
        font-size: .75rem;
        font-weight: 700;
        height: 34px;
        justify-content: center;
    }

    .numbering-filter__panel {
        background: #f8fafc;
        border: 1px solid #e8edf3;
        border-radius: 14px;
        padding: 1rem;
    }

    .numbering-filter__panel-title {
        color: #1f2a44;
        font-size: .82rem;
        font-weight: 800;
        letter-spacing: .04em;
        margin-bottom: .85rem;
        text-transform: uppercase;
    }

    .numbering-filter .form-control,
    .numbering-filter .form-select {
        min-height: 42px;
    }

    .numbering-filter input[type="date"] {
        min-width: 0;
    }

    .numbering-filter__actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(130px, auto));
        gap: .65rem;
    }

    .numbering-filter-trigger {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }

    .numbering-filter-trigger__actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    @media (max-width: 1399.98px) {
        .numbering-filter__main {
            grid-template-columns: minmax(280px, 1fr) minmax(180px, .7fr) minmax(220px, .8fr);
        }

        .numbering-filter__actions {
            grid-column: 1 / -1;
            grid-template-columns: repeat(3, minmax(150px, 1fr));
        }

        .numbering-filter__advanced {
            grid-template-columns: repeat(2, minmax(210px, 1fr));
        }
    }

    @media (max-width: 991.98px) {
        .numbering-filter__main,
        .numbering-filter__advanced {
            grid-template-columns: 1fr 1fr;
        }

        .numbering-filter__actions {
            grid-template-columns: 1fr 1fr;
        }

        .numbering-filter__actions .btn:last-child {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 575.98px) {
        .numbering-filter__main,
        .numbering-filter__advanced,
        .numbering-filter__actions {
            grid-template-columns: 1fr;
        }

        .numbering-filter__actions .btn:last-child {
            grid-column: auto;
        }
    }
</style>
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Data Penomoran', 'subtitle' => 'Numbering Register'])
@include('invoice-verification.partials.flash')

@php
    $activeFilterCount = collect($filters)
        ->except(['search'])
        ->filter(fn ($value) => filled($value))
        ->count() + (filled($filters['search']) ? 1 : 0);
@endphp

<div class="card iv-table-card">
    <div class="card-header bg-white border-bottom">
        <div class="numbering-filter-trigger">
            <div>
                <div class="fw-semibold">Filter Data Penomoran</div>
                <div class="text-muted small">
                    {{ $activeFilterCount > 0 ? $activeFilterCount.' filter aktif' : 'Tidak ada filter aktif' }}
                </div>
            </div>
            <div class="numbering-filter-trigger__actions">
                <button class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1" type="button" data-bs-toggle="modal" data-bs-target="#numbering-filter-modal">
                    <iconify-icon icon="solar:filter-outline" class="fs-18"></iconify-icon>
                    <span>Filter</span>
                </button>
                <a href="{{ route('invoice-verification.numbering-registers.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1"><iconify-icon icon="solar:restart-outline" class="fs-18"></iconify-icon><span>Reset</span></a>
                <a href="{{ route('invoice-verification.numbering-registers.export', request()->except('page')) }}" class="btn btn-outline-success d-inline-flex align-items-center justify-content-center gap-1">
                    <iconify-icon icon="solar:download-outline" class="fs-18"></iconify-icon>
                    <span>Download Excel</span>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 iv-table" style="--iv-table-min-width: 1900px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Detail</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.numbering-registers.index', 'column' => 'register_number', 'label' => 'Register Number'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.numbering-registers.index', 'column' => 'vendor_name', 'label' => 'Vendor'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.numbering-registers.index', 'column' => 'invoice_number', 'label' => 'Invoice'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.numbering-registers.index', 'column' => 'contract_number', 'label' => 'Kontrak / Memo'])</th>
                        <th>Faktur Pajak</th>
                        <th>Organisasi</th>
                        <th>Tanggal</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.numbering-registers.index', 'column' => 'invoice_value', 'label' => 'Nilai Invoice'])</th>
                        <th>Pajak</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registers as $register)
                        <tr>
                            <td class="ps-4">
                                <button class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="modal" data-bs-target="#register-modal-{{ $register->id }}">
                                    <iconify-icon icon="solar:eye-outline" class="fs-16"></iconify-icon>
                                    Detail
                                </button>
                            </td>
                            <td>{{ $register->register_number }}</td>
                            <td><div class="text-truncate iv-cell-truncate" style="--iv-cell-width: 210px;" title="{{ $register->vendor_name }}">{{ $register->vendor_name }}</div></td>
                            <td>
                                <div class="fw-semibold">{{ $register->invoice_number }}</div>
                                <div class="text-muted small">Tgl: {{ $register->invoice_date?->format('d M Y') ?? '-' }}</div>
                                <div class="text-muted small text-truncate iv-cell-truncate" style="--iv-cell-width: 220px;" title="{{ $register->account_number ?? '-' }}{{ $register->account_name ? ' · '.$register->account_name : '' }}">{{ $register->account_number ?? '-' }}{{ $register->account_name ? ' · '.$register->account_name : '' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $register->contract_number ?? '-' }}</div>
                                <div class="text-muted small">{{ $register->memo_number ?? '-' }}</div>
                            </td>
                            <td>
                                <div>{{ $register->tax_invoice_number ?? '-' }}</div>
                                <div class="text-muted small">{{ $register->tax_invoice_date?->format('d M Y') ?? '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-truncate iv-cell-truncate" style="--iv-cell-width: 190px;" title="{{ $register->division_name ?? $register->transaction?->division?->name ?? '-' }}">{{ $register->division_name ?? $register->transaction?->division?->name ?? '-' }}</div>
                                <div class="text-muted small text-truncate iv-cell-truncate" style="--iv-cell-width: 190px;" title="{{ $register->department_head_name ?? '-' }}">{{ $register->department_head_name ?? '-' }}</div>
                            </td>
                            <td>
                                <div>Terima: {{ $register->received_date?->format('d M Y') ?? '-' }}</div>
                                <div class="text-muted small">Upload: {{ $register->upload_date?->format('d M Y') ?? '-' }}</div>
                                <div class="text-muted small">Gen: {{ $register->generated_at?->format('d M Y H:i') }}</div>
                            </td>
                            <td>{{ number_format((float) $register->invoice_value, 2, ',', '.') }}</td>
                            <td>
                                <div>PPN: {{ number_format((float) $register->ppn_value, 2, ',', '.') }}</div>
                                <div class="text-muted small">PPh: {{ number_format((float) $register->pph_value, 2, ',', '.') }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">Belum ada numbering register.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-top">
            {{ $registers->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="numbering-filter-modal" tabindex="-1" aria-labelledby="numbering-filter-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="GET" class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Filter</div>
                    <h5 class="modal-title" id="numbering-filter-modal-label">Filter Data Penomoran</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="numbering-filter">
                    <div class="numbering-filter__panel">
                        <div class="numbering-filter__panel-title">Filter Utama</div>
                        <div class="numbering-filter__main">
                            <div>
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><iconify-icon icon="solar:magnifer-outline" class="fs-18"></iconify-icon></span>
                                    <input type="search" class="form-control" name="search" value="{{ $filters['search'] }}" placeholder="Register, vendor, invoice, memo, kontrak, faktur pajak">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">Semua status</option>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Jenis Transaksi</label>
                                <select class="form-select" name="transaction_type_id">
                                    <option value="">Semua jenis</option>
                                    @foreach ($transactionTypes as $type)
                                        <option value="{{ $type->id }}" @selected($filters['transaction_type_id'] === $type->id)>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="numbering-filter__panel">
                        <div class="numbering-filter__panel-title">Rentang Data</div>
                        <div class="numbering-filter__advanced">
                            <div>
                                <label class="form-label">Tanggal Terima</label>
                                <div class="numbering-filter__range">
                                    <input type="date" class="form-control" name="received_from" value="{{ $filters['received_from'] }}">
                                    <span class="numbering-filter__range-separator">s/d</span>
                                    <input type="date" class="form-control" name="received_to" value="{{ $filters['received_to'] }}">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Tanggal Invoice</label>
                                <div class="numbering-filter__range">
                                    <input type="date" class="form-control" name="invoice_from" value="{{ $filters['invoice_from'] }}">
                                    <span class="numbering-filter__range-separator">s/d</span>
                                    <input type="date" class="form-control" name="invoice_to" value="{{ $filters['invoice_to'] }}">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Tanggal Upload</label>
                                <div class="numbering-filter__range">
                                    <input type="date" class="form-control" name="upload_from" value="{{ $filters['upload_from'] }}">
                                    <span class="numbering-filter__range-separator">s/d</span>
                                    <input type="date" class="form-control" name="upload_to" value="{{ $filters['upload_to'] }}">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Generated</label>
                                <div class="numbering-filter__range">
                                    <input type="date" class="form-control" name="generated_from" value="{{ $filters['generated_from'] }}">
                                    <span class="numbering-filter__range-separator">s/d</span>
                                    <input type="date" class="form-control" name="generated_to" value="{{ $filters['generated_to'] }}">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Range Nilai Invoice</label>
                                <div class="numbering-filter__range">
                                    <input type="text" inputmode="numeric" class="form-control" name="invoice_value_min" value="{{ $filters['invoice_value_min'] }}" placeholder="Min" data-rupiah-input>
                                    <span class="numbering-filter__range-separator">s/d</span>
                                    <input type="text" inputmode="numeric" class="form-control" name="invoice_value_max" value="{{ $filters['invoice_value_max'] }}" placeholder="Max" data-rupiah-input>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('invoice-verification.numbering-registers.index') }}" class="btn btn-outline-secondary me-auto">
                    Reset Filter
                </a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="solar:filter-outline" class="fs-18"></iconify-icon>
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>
</div>

@foreach ($registers as $register)
    @php
        $typeCode = $register->transaction?->transactionType?->code;
        $typeCode = $typeCode instanceof \BackedEnum ? $typeCode->value : $typeCode;
        $isPpa = in_array($typeCode, [
            \App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode::PPA->value,
            \App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode::PPA_NON_CONTRACT->value,
        ], true);
        $isSpu = $typeCode === \App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode::SPU->value;
        $isSpuk = $typeCode === \App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode::SPUK->value;
        $isKasKecil = $typeCode === \App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode::KAS_KECIL->value;
        $transaction = $register->transaction;
    @endphp
    <div class="modal fade" id="register-modal-{{ $register->id }}" tabindex="-1" aria-labelledby="register-modal-label-{{ $register->id }}" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form method="POST" action="{{ route('invoice-verification.numbering-registers.update', $register) }}" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Edit Data Penomoran</div>
                        <h5 class="modal-title" id="register-modal-label-{{ $register->id }}">{{ $register->register_number }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="alert alert-light border mb-0">
                                <div class="fw-semibold">{{ $register->vendor_name }}</div>
                                <div class="text-muted small">{{ $register->transaction?->transactionType?->name ?? 'Transaksi' }} · {{ $register->transaction?->status?->label() ?? '-' }}</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <h6 class="text-primary mb-0">Dokumen</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nomor Dokumen</label>
                            <input class="form-control" name="register_number" value="{{ old('register_number', $register->register_number) }}" required>
                        </div>
                        @if ($isPpa)
                            <div class="col-md-4">
                                <label class="form-label">Nomor Invoice</label>
                                <input class="form-control" name="invoice_number" value="{{ old('invoice_number', $register->invoice_number) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nomor Kontrak</label>
                                <input class="form-control" name="contract_number" value="{{ old('contract_number', $register->contract_number) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nomor Memo</label>
                                <input class="form-control" name="memo_number" value="{{ old('memo_number', $register->memo_number) }}">
                            </div>
                        @elseif ($isSpu)
                            <div class="col-md-4">
                                <label class="form-label">Nomor Memo</label>
                                <input class="form-control" name="memo_number" value="{{ old('memo_number', $register->memo_number) }}">
                            </div>
                        @elseif ($isSpuk)
                            <div class="col-md-4">
                                <label class="form-label">Nomor SPU</label>
                                <input class="form-control" value="{{ $transaction?->parentSpuTransaction?->registration_number ?? '-' }}" readonly>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Terima</label>
                            <input type="date" class="form-control" name="received_date" value="{{ old('received_date', optional($register->received_date)->format('Y-m-d')) }}" required>
                        </div>
                        @if ($isPpa)
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Invoice</label>
                                <input type="date" class="form-control" name="invoice_date" value="{{ old('invoice_date', optional($register->invoice_date)->format('Y-m-d')) }}">
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Upload</label>
                            <input type="date" class="form-control" name="upload_date" value="{{ old('upload_date', optional($register->upload_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hardcopy Diterima</label>
                            <div class="form-check form-switch border rounded px-3 py-2 d-flex align-items-center gap-2" style="min-height: 42px;">
                                <input type="hidden" name="hardcopy_received" value="0">
                                <input class="form-check-input ms-0" type="checkbox" role="switch" name="hardcopy_received" value="1" id="hardcopy-{{ $register->id }}" @checked(old('hardcopy_received', $register->hardcopy_received))>
                                <label class="form-check-label" for="hardcopy-{{ $register->id }}">Ya</label>
                            </div>
                        </div>
                        @if ($isPpa)
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Faktur Pajak</label>
                                <input type="date" class="form-control" name="tax_invoice_date" value="{{ old('tax_invoice_date', optional($register->tax_invoice_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nomor Faktur Pajak</label>
                                <input class="form-control" name="tax_invoice_number" value="{{ old('tax_invoice_number', $register->tax_invoice_number) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nomor GR</label>
                                <input class="form-control" name="gr_number" value="{{ old('gr_number', $register->gr_number) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenis Jurnal</label>
                                <input class="form-control" name="journal_type" value="{{ old('journal_type', $register->journal_type) }}">
                            </div>
                        @elseif ($isSpu)
                            <div class="col-md-4">
                                <label class="form-label">Jenis Jurnal</label>
                                <input class="form-control" name="journal_type" value="{{ old('journal_type', $register->journal_type) }}">
                            </div>
                        @endif
                        <div class="col-md-12">
                            <label class="form-label">Uraian Transaksi</label>
                            <input class="form-control" name="description" value="{{ old('description', $register->description) }}">
                        </div>

                        @if ($isPpa)
                            <div class="col-12 pt-2">
                                <h6 class="text-primary mb-0">Nilai dan Pajak</h6>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nilai Invoice</label>
                                <input type="text" inputmode="numeric" class="form-control @error('invoice_value') is-invalid @enderror" name="invoice_value" value="{{ old('invoice_value', $register->invoice_value) }}" data-rupiah-input>
                                @error('invoice_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">PPN</label>
                                <input type="text" inputmode="numeric" class="form-control @error('ppn_value') is-invalid @enderror" name="ppn_value" value="{{ old('ppn_value', $register->ppn_value) }}" data-rupiah-input>
                                @error('ppn_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">PPh</label>
                                <input type="text" inputmode="numeric" class="form-control @error('pph_value') is-invalid @enderror" name="pph_value" value="{{ old('pph_value', $register->pph_value) }}" data-rupiah-input>
                                @error('pph_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Discount/Denda/Materai</label>
                                <input type="text" inputmode="numeric" class="form-control @error('discount_deduction_stamp_value') is-invalid @enderror" name="discount_deduction_stamp_value" value="{{ old('discount_deduction_stamp_value', $register->discount_deduction_stamp_value) }}" data-rupiah-input>
                                @error('discount_deduction_stamp_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @elseif ($isSpu)
                            <div class="col-12 pt-2">
                                <h6 class="text-primary mb-0">Data SPU</h6>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nilai SPU</label>
                                <input class="form-control" value="{{ $transaction?->spu_amount !== null ? 'Rp '.number_format((float) $transaction->spu_amount, 0, ',', '.') : '-' }}" readonly>
                            </div>
                        @elseif ($isSpuk)
                            <div class="col-12 pt-2">
                                <h6 class="text-primary mb-0">Data SPUK</h6>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nilai SPU</label>
                                <input class="form-control" value="{{ $transaction?->parentSpuTransaction?->spu_amount !== null ? 'Rp '.number_format((float) $transaction->parentSpuTransaction->spu_amount, 0, ',', '.') : '-' }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nilai Realisasi</label>
                                <input class="form-control" value="{{ $transaction?->accountability_amount !== null ? 'Rp '.number_format((float) $transaction->accountability_amount, 0, ',', '.') : '-' }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Selisih</label>
                                <input class="form-control" value="{{ $transaction?->remaining_amount !== null ? 'Rp '.number_format((float) $transaction->remaining_amount, 0, ',', '.') : '-' }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status Jurnal</label>
                                <input class="form-control" name="journal_status" value="{{ old('journal_status', $register->journal_status) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Pengembalian</label>
                                <input type="date" class="form-control" name="return_date" value="{{ old('return_date', optional($register->return_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nominal Pengembalian</label>
                                <input type="text" inputmode="numeric" class="form-control @error('return_amount') is-invalid @enderror" name="return_amount" value="{{ old('return_amount', $register->return_amount) }}" data-rupiah-input>
                                @error('return_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status SPUK</label>
                                <input class="form-control" name="spuk_status" value="{{ old('spuk_status', $register->spuk_status) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes SPUK</label>
                                <textarea class="form-control" name="spuk_notes" rows="2">{{ old('spuk_notes', $register->spuk_notes) }}</textarea>
                            </div>
                        @elseif ($isKasKecil)
                            <div class="col-12 pt-2">
                                <h6 class="text-primary mb-0">Data Kas Kecil</h6>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Plafon Petty Cash</label>
                                <input class="form-control" value="{{ $transaction?->petty_cash_ceiling_snapshot !== null ? 'Rp '.number_format((float) $transaction->petty_cash_ceiling_snapshot, 0, ',', '.') : '-' }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sisa Petty Cash</label>
                                <input class="form-control" value="{{ $transaction?->petty_cash_remaining_amount !== null ? 'Rp '.number_format((float) $transaction->petty_cash_remaining_amount, 0, ',', '.') : '-' }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nilai Top Up</label>
                                <input class="form-control" value="{{ $transaction?->petty_cash_top_up_amount !== null ? 'Rp '.number_format((float) $transaction->petty_cash_top_up_amount, 0, ',', '.') : '-' }}" readonly>
                            </div>
                        @endif

                        <div class="col-12 pt-2">
                            <h6 class="text-primary mb-0">{{ $isSpuk ? 'Bank dan Pengembalian' : 'Bank dan Pembayaran' }}</h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bank</label>
                            <input class="form-control" name="bank_name" value="{{ old('bank_name', $register->bank_name) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">No Rekening</label>
                            <input class="form-control @error('account_number') is-invalid @enderror" name="account_number" value="{{ old('account_number', $register->account_number) }}" inputmode="numeric" pattern="[0-9]{6,30}" maxlength="30" autocomplete="off" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            @error('account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Atas Nama Rekening</label>
                            <input class="form-control" name="account_name" value="{{ old('account_name', $register->account_name) }}">
                        </div>
                        @if (! $isSpuk)
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Ekspedisi</label>
                                <input type="date" class="form-control" name="payment_expedition_date" value="{{ old('payment_expedition_date', optional($register->payment_expedition_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Pembayaran</label>
                                <input type="date" class="form-control" name="payment_date" value="{{ old('payment_date', optional($register->payment_date)->format('Y-m-d')) }}">
                            </div>
                        @endif

                        @if ($isSpu || $isSpuk || $isKasKecil)
                            <div class="col-12 pt-2">
                                <h6 class="text-primary mb-0">Organisasi</h6>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Divisi</label>
                                <input class="form-control" name="division_name" value="{{ old('division_name', $register->division_name) }}">
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <iconify-icon icon="solar:diskette-outline" class="fs-18"></iconify-icon>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endforeach

@include('invoice-verification.partials.rupiah-input')
@endsection
