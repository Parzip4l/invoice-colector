@extends('layouts.vertical', ['subtitle' => 'Create Transaction'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Transactions', 'subtitle' => 'Create Transaction'])
@include('invoice-verification.partials.flash')

<form method="POST" action="{{ route('invoice-verification.transactions.store') }}">
    @csrf
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-1">Informasi Transaksi</h5>
                    <p class="text-muted mb-0">Buat draft transaksi sesuai tipe yang tersedia untuk akun Anda.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Jenis Transaksi</label>
                            <select name="transaction_type_id" id="transaction_type_id" class="form-select @error('transaction_type_id') is-invalid @enderror" required>
                                <option value="">Pilih</option>
                                @foreach ($transactionTypes as $type)
                                    <option value="{{ $type->id }}" data-code="{{ $type->code?->value }}" @selected((string) old('transaction_type_id') === (string) $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('transaction_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vendor</label>
                            <input type="hidden" name="vendor_id" value="{{ old('vendor_id', $linkedVendor?->id) }}">
                            <input type="text" class="form-control" value="{{ $linkedVendor?->name ?? auth()->user()?->name }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Divisi</label>
                            <input type="hidden" name="division_id" value="{{ auth()->user()?->division_id }}">
                            <input type="text" class="form-control" value="{{ $currentDivision?->name ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Departemen</label>
                            <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                                <option value="">Pilih departemen</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected((string) old('department_id', auth()->user()?->department_id) === (string) $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Memo Permohonan</label>
                            <select name="memo_request_id" id="memo_request_id" class="form-select @error('memo_request_id') is-invalid @enderror" required>
                                <option value="">Pilih memo</option>
                                @foreach ($memoRequests as $memo)
                                    <option value="{{ $memo->id }}" data-department-id="{{ $memo->department_id }}" @selected((string) old('memo_request_id') === (string) $memo->id)>{{ $memo->memo_number }} - {{ $memo->subject }}</option>
                                @endforeach
                            </select>
                            @error('memo_request_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" data-field="ppa-contract">
                            <label class="form-label">Nomor Kontrak</label>
                            <select name="agreement_reference_id" id="agreement_reference_id" class="form-select @error('agreement_reference_id') is-invalid @enderror">
                                <option value="">Pilih kontrak</option>
                                @foreach ($agreementReferences as $agreement)
                                    <option value="{{ $agreement->id }}" data-department-id="{{ $agreement->department_id }}" data-vendor-id="{{ $agreement->vendor_id }}" @selected((string) old('agreement_reference_id') === (string) $agreement->id)>{{ $agreement->contract_number }}</option>
                                @endforeach
                            </select>
                            @error('agreement_reference_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6" data-field="activity">
                            <label class="form-label">Nama Kegiatan</label>
                            <input class="form-control @error('activity_name') is-invalid @enderror" name="activity_name" value="{{ old('activity_name') }}">
                            @error('activity_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" data-field="bank">
                            <label class="form-label">Nama Bank</label>
                            <input class="form-control @error('transaction_bank_name') is-invalid @enderror" name="transaction_bank_name" value="{{ old('transaction_bank_name') }}">
                            @error('transaction_bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" data-field="bank">
                            <label class="form-label">Nomor Rekening</label>
                            <input class="form-control @error('transaction_account_number') is-invalid @enderror" name="transaction_account_number" value="{{ old('transaction_account_number') }}" inputmode="numeric" pattern="[0-9]{6,30}" maxlength="30" autocomplete="off" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            @error('transaction_account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" data-field="spu-amount">
                            <label class="form-label">Nilai SPU</label>
                            <input type="text" inputmode="numeric" class="form-control @error('spu_amount') is-invalid @enderror" name="spu_amount" value="{{ old('spu_amount') }}" data-rupiah-input>
                            @error('spu_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" data-field="spuk">
                            <label class="form-label">Nomor SPU</label>
                            <select name="parent_spu_transaction_id" id="parent_spu_transaction_id" class="form-select @error('parent_spu_transaction_id') is-invalid @enderror">
                                <option value="">Pilih SPU</option>
                                @foreach ($spuTransactions as $spu)
                                    <option value="{{ $spu->id }}" data-activity="{{ $spu->activity_name }}" data-amount="{{ $spu->spu_amount }}" @selected((string) old('parent_spu_transaction_id') === (string) $spu->id)>{{ $spu->registration_number }} - {{ $spu->activity_name }}</option>
                                @endforeach
                            </select>
                            @error('parent_spu_transaction_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" data-field="spuk">
                            <label class="form-label">Nilai Pertanggungjawaban</label>
                            <input type="text" inputmode="numeric" id="accountability_amount" class="form-control @error('accountability_amount') is-invalid @enderror" name="accountability_amount" value="{{ old('accountability_amount') }}" data-rupiah-input>
                            @error('accountability_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" data-field="spuk">
                            <label class="form-label">Nilai Sisa</label>
                            <input id="remaining_preview" class="form-control" readonly>
                        </div>
                        <div class="col-md-6" data-field="petty-cash">
                            <label class="form-label">Periode</label>
                            <input class="form-control @error('period') is-invalid @enderror" name="period" value="{{ old('period') }}" placeholder="contoh: Juli 2026">
                            @error('period')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" data-field="petty-cash">
                            <label class="form-label">Plafon Kas Kecil</label>
                            <input id="petty_cash_ceiling" class="form-control" value="{{ $currentDivision?->petty_cash_ceiling !== null ? 'Rp ' . number_format((float) $currentDivision->petty_cash_ceiling, 0, ',', '.') : '-' }}" readonly>
                        </div>
                        <div class="col-md-6" data-field="petty-cash">
                            <label class="form-label">Nilai Sisa Kas Kecil</label>
                            <input type="text" inputmode="numeric" id="petty_cash_remaining_amount" class="form-control @error('petty_cash_remaining_amount') is-invalid @enderror" name="petty_cash_remaining_amount" value="{{ old('petty_cash_remaining_amount') }}" data-rupiah-input>
                            @error('petty_cash_remaining_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" data-field="petty-cash">
                            <label class="form-label">Nilai Top Up</label>
                            <input id="petty_cash_top_up_preview" class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" rows="3" name="description">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary">Simpan Draft Transaksi</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
@include('invoice-verification.partials.rupiah-input')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('transaction_type_id');
    const parentSpu = document.getElementById('parent_spu_transaction_id');
    const accountability = document.getElementById('accountability_amount');
    const remainingPreview = document.getElementById('remaining_preview');
    const pettyRemaining = document.getElementById('petty_cash_remaining_amount');
    const pettyCeiling = Number('{{ $currentDivision?->petty_cash_ceiling ?? 0 }}');
    const pettyTopUp = document.getElementById('petty_cash_top_up_preview');

    function currentCode() {
        return typeSelect?.selectedOptions?.[0]?.dataset?.code || '';
    }

    function toggleFields() {
        const code = currentCode();
        document.querySelectorAll('[data-field]').forEach((el) => el.classList.add('d-none'));
        const show = [];
        if (code === 'PPA') show.push('ppa-contract');
        if (code === 'PPA_NON_CONTRACT') show.push('activity', 'bank');
        if (code === 'SPU') show.push('activity', 'bank', 'spu-amount');
        if (code === 'SPUK') show.push('spuk');
        if (code === 'KAS_KECIL') show.push('petty-cash');
        show.forEach((name) => document.querySelectorAll(`[data-field="${name}"]`).forEach((el) => el.classList.remove('d-none')));
        updateSpuk();
        updatePettyCash();
    }

    function updateSpuk() {
        const amount = Number(parentSpu?.selectedOptions?.[0]?.dataset?.amount || 0);
        const accountabilityValue = Number(String(accountability?.value || '').replace(/[^0-9]/g, ''));
        if (remainingPreview) remainingPreview.value = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount - accountabilityValue);
    }

    function updatePettyCash() {
        const remaining = Number(String(pettyRemaining?.value || '').replace(/[^0-9]/g, ''));
        if (pettyTopUp) pettyTopUp.value = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(pettyCeiling - remaining);
    }

    typeSelect?.addEventListener('change', toggleFields);
    parentSpu?.addEventListener('change', updateSpuk);
    accountability?.addEventListener('input', updateSpuk);
    pettyRemaining?.addEventListener('input', updatePettyCash);
    toggleFields();
});
</script>
@endsection
