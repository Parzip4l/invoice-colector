@extends('layouts.vertical', ['subtitle' => 'Numbering Register'])

@section('css')
@include('invoice-verification.partials.table-ui')
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Data Penomoran', 'subtitle' => 'Numbering Register'])
@include('invoice-verification.partials.flash')

<div class="card iv-table-card">
    <div class="card-header bg-white border-bottom">
        <form method="GET" class="iv-table-toolbar" style="--iv-filter-count: 0;">
            <div>
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><iconify-icon icon="solar:magnifer-outline" class="fs-18"></iconify-icon></span>
                    <input type="search" class="form-control" name="search" value="{{ $search }}" placeholder="Register, vendor, invoice, memo, bank">
                </div>
            </div>
            <button class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1"><iconify-icon icon="solar:filter-outline" class="fs-18"></iconify-icon><span>Filter</span></button>
            <a href="{{ route('invoice-verification.numbering-registers.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1"><iconify-icon icon="solar:restart-outline" class="fs-18"></iconify-icon><span>Reset</span></a>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 iv-table" style="--iv-table-min-width: 1200px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.numbering-registers.index', 'column' => 'register_number', 'label' => 'Register Number'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.numbering-registers.index', 'column' => 'vendor_name', 'label' => 'Vendor'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.numbering-registers.index', 'column' => 'invoice_number', 'label' => 'Invoice'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.numbering-registers.index', 'column' => 'memo_number', 'label' => 'Memo'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.numbering-registers.index', 'column' => 'invoice_value', 'label' => 'Nilai Invoice'])</th>
                        <th>@include('invoice-verification.partials.sort-link', ['route' => 'invoice-verification.numbering-registers.index', 'column' => 'generated_at', 'label' => 'Generated'])</th>
                        <th class="text-end">Edit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registers as $register)
                        <tr>
                            <td class="ps-4">{{ $register->register_number }}</td>
                            <td><div class="text-truncate iv-cell-truncate" style="--iv-cell-width: 210px;" title="{{ $register->vendor_name }}">{{ $register->vendor_name }}</div></td>
                            <td>
                                <div class="fw-semibold">{{ $register->invoice_number }}</div>
                                <div class="text-muted small text-truncate iv-cell-truncate" style="--iv-cell-width: 220px;" title="{{ $register->account_number ?? '-' }}{{ $register->account_name ? ' · '.$register->account_name : '' }}">{{ $register->account_number ?? '-' }}{{ $register->account_name ? ' · '.$register->account_name : '' }}</div>
                            </td>
                            <td>{{ $register->memo_number ?? '-' }}</td>
                            <td>{{ number_format((float) $register->invoice_value, 2, ',', '.') }}</td>
                            <td>{{ $register->generated_at?->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#register-{{ $register->id }}">
                                    Edit
                                </button>
                            </td>
                        </tr>
                        <tr class="collapse" id="register-{{ $register->id }}">
                            <td colspan="7">
                                <form method="POST" action="{{ route('invoice-verification.numbering-registers.update', $register) }}" class="row g-2 align-items-end">
                                    @csrf
                                    @method('PUT')
                                    <div class="col-md-3">
                                        <label class="form-label">Nomor Dokumen</label>
                                        <input class="form-control" name="register_number" value="{{ old('register_number', $register->register_number) }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Nomor Invoice</label>
                                        <input class="form-control" name="invoice_number" value="{{ old('invoice_number', $register->invoice_number) }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Tanggal Terima</label>
                                        <input type="date" class="form-control" name="received_date" value="{{ old('received_date', optional($register->received_date)->format('Y-m-d')) }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Tanggal Invoice</label>
                                        <input type="date" class="form-control" name="invoice_date" value="{{ old('invoice_date', optional($register->invoice_date)->format('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-2">
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
                                    <div class="col-md-2">
                                        <label class="form-label">Nilai Invoice</label>
                                        <input type="text" inputmode="numeric" class="form-control @error('invoice_value') is-invalid @enderror" name="invoice_value" value="{{ old('invoice_value', $register->invoice_value) }}" data-rupiah-input>
                                        @error('invoice_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">PPN</label>
                                        <input type="text" inputmode="numeric" class="form-control @error('ppn_value') is-invalid @enderror" name="ppn_value" value="{{ old('ppn_value', $register->ppn_value) }}" data-rupiah-input>
                                        @error('ppn_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Uraian Transaksi</label>
                                        <input class="form-control" name="description" value="{{ old('description', $register->description) }}">
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-primary w-100">Simpan</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada numbering register.</td>
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
@include('invoice-verification.partials.rupiah-input')
@endsection
