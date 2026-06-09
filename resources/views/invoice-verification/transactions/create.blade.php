@extends('layouts.vertical', ['subtitle' => 'Create Transaction'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Transactions', 'subtitle' => 'Create Transaction'])
@include('invoice-verification.partials.flash')

<form method="POST" action="{{ route('invoice-verification.transactions.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-1">Informasi Transaksi</h5>
                    <p class="text-muted mb-0">Admin membuat draft tagihan pekerjaan. Vendor akan melengkapi invoice dan upload dokumen dari menu Daftar Transaksi.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Jenis Transaksi</label>
                            <select name="transaction_type_id" class="form-select" required>
                                <option value="">Pilih</option>
                                @foreach ($transactionTypes as $type)
                                    <option value="{{ $type->id }}" @selected((string) old('transaction_type_id') === (string) $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" id="vendor_id" class="form-select" required>
                                <option value="">Pilih vendor</option>
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected((string) old('vendor_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Divisi</label>
                            <input type="hidden" name="division_id" value="{{ auth()->user()?->division_id }}">
                            <input type="text" class="form-control" value="{{ $currentDivision?->name ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Departemen</label>
                            <select name="department_id" id="department_id" class="form-select" required>
                                <option value="">Pilih departemen</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected((string) old('department_id', auth()->user()?->department_id) === (string) $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Memo Request</label>
                            <select name="memo_request_id" id="memo_request_id" class="form-select" required>
                                <option value="">Pilih memo permohonan</option>
                                @foreach ($memoRequests as $memo)
                                    <option value="{{ $memo->id }}"
                                        data-department-id="{{ $memo->department_id }}"
                                        @selected((string) old('memo_request_id') === (string) $memo->id)>
                                        {{ $memo->memo_number }} - {{ $memo->subject }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Memo dibuat dan diunggah lebih dulu oleh Admin Divisi dari menu Master Data.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Agreement Reference</label>
                            <select name="agreement_reference_id" id="agreement_reference_id" class="form-select">
                                <option value="">Pilih kontrak terdaftar</option>
                                @foreach ($agreementReferences as $agreement)
                                    <option value="{{ $agreement->id }}"
                                        data-department-id="{{ $agreement->department_id }}"
                                        data-vendor-id="{{ $agreement->vendor_id }}"
                                        @selected((string) old('agreement_reference_id') === (string) $agreement->id)>
                                        {{ $agreement->contract_number }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Untuk tagihan lanjutan, cukup pilih kontrak yang sudah pernah didaftarkan tanpa input ulang nomor kontrak.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" rows="4" name="description">{{ old('description') }}</textarea>
                            <small class="text-muted">Contoh: pekerjaan yang akan ditagihkan vendor.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-1">Draft Workflow</h5>
                    <p class="text-muted mb-0">Setelah disimpan, transaksi akan muncul di akun vendor terkait untuk proses upload dokumen tagihan.</p>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 mb-0">
                        <div class="fw-semibold">Tahap berikutnya</div>
                        <div>Vendor mengisi nomor invoice, tanggal invoice, nilai invoice, bank, dan upload dokumen pada tombol <strong>Upload Dokumen</strong>.</div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary w-100">Simpan Draft Transaksi</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const departmentSelect = document.getElementById('department_id');
        const vendorSelect = document.getElementById('vendor_id');
        const memoSelect = document.getElementById('memo_request_id');
        const agreementSelect = document.getElementById('agreement_reference_id');

        const syncSelectOptions = (selectElement, departmentId, vendorId = '') => {
            if (!selectElement) {
                return;
            }

            let hasSelectedOption = false;

            Array.from(selectElement.options).forEach((option, index) => {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                const optionDepartmentId = option.dataset.departmentId;
                const optionVendorId = option.dataset.vendorId;
                const isVisible = (!departmentId || optionDepartmentId === departmentId)
                    && (!vendorId || !optionVendorId || optionVendorId === vendorId);

                option.hidden = !isVisible;

                if (!isVisible && option.selected) {
                    option.selected = false;
                }

                if (isVisible && option.selected) {
                    hasSelectedOption = true;
                }
            });

            if (!hasSelectedOption) {
                selectElement.value = '';
            }
        };

        const applyDepartmentFilter = () => {
            const departmentId = departmentSelect?.value ?? '';
            const vendorId = vendorSelect?.value ?? '';

            syncSelectOptions(memoSelect, departmentId);
            syncSelectOptions(agreementSelect, departmentId, vendorId);
        };

        departmentSelect?.addEventListener('change', applyDepartmentFilter);
        vendorSelect?.addEventListener('change', applyDepartmentFilter);
        applyDepartmentFilter();
    });
</script>
@endsection
