@extends('layouts.vertical', ['subtitle' => 'Pengajuan Penggunaan Anggaran & Lembar Checklist'])

@section('content')
@include('layouts.partials.page-title', [
    'title' => $transaction->isPpaNonContract() ? 'Pengajuan Penggunaan Anggaran (PPA Non Kontrak)' : 'PPA Verification Sheet',
    'subtitle' => $transaction->isPpaNonContract() ? 'Formulir LRTJ-FR-KDA-001 & Lembar Verifikasi LRTJ-FR-KDA-004' : 'Checklist Form'
])
@include('invoice-verification.partials.flash')

@if ($transaction->isPpaNonContract())
<style>
    .ppa-form-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.04);
        background: #ffffff;
        margin-bottom: 24px;
        overflow: hidden;
    }
    .ppa-card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        padding: 16px 20px;
    }
    .ppa-doc-badge {
        font-family: monospace;
        font-weight: 700;
        background: #f1f5f9;
        color: #0f172a;
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
    }
    .table-ppa th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        vertical-align: middle;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-ppa td {
        vertical-align: middle;
        padding: 10px 12px;
    }
    .category-row td {
        background: #f1f5f9;
        color: #1e293b;
        font-weight: 700;
        font-size: 0.84rem;
        border-top: 1px solid #cbd5e1;
        border-bottom: 1px solid #cbd5e1;
    }
    .sticky-action-bar {
        position: sticky;
        bottom: 16px;
        z-index: 10;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 20px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    }
    .budget-autocomplete-dropdown {
        position: fixed;
        z-index: 9999;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        max-height: 280px;
        overflow-y: auto;
    }
    .budget-autocomplete-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.82rem;
        transition: background-color 0.15s ease;
    }
    .budget-autocomplete-item:hover, .budget-autocomplete-item.active {
        background-color: #eff6ff;
    }
    .budget-autocomplete-item:last-child {
        border-bottom: none;
    }
    .highlight-success-field {
        animation: fieldSuccessGlow 1.2s ease-out;
    }
    @keyframes fieldSuccessGlow {
        0% { background-color: #dcfce7; border-color: #22c55e; }
        100% { background-color: #ffffff; }
    }
</style>

@php
    $budgetItems = old('budget_items', $sheet->budget_items_json ?: [
        [
            'kode_rab' => '',
            'nama_kegiatan' => $transaction->invoiceMetadata?->description ?? $transaction->description ?? 'Konsumsi Kegiatan',
            'nilai_realisasi' => (float) (($transaction->invoiceMetadata?->invoice_value ?? 0) + ($transaction->invoiceMetadata?->ppn_value ?? 0)),
        ]
    ]);
    $totalRealization = collect($budgetItems)->sum(fn ($b) => (float) ($b['nilai_realisasi'] ?? 0));
    $advancePayment = old('advance_payment_value', $sheet->advance_payment_value ?? 0);
    $exchangeRate = old('exchange_rate_value', $sheet->exchange_rate_value ?? 1.00);
@endphp

<!-- Header Information Card -->
<div class="card ppa-form-card mb-3">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="ppa-doc-badge">PPA NON KONTRAK</span>
                    <h5 class="mb-0 fw-bold">{{ $transaction->registration_number }}</h5>
                </div>
                <div class="text-muted small">
                    {{ $transaction->division?->name ?? 'Divisi' }} / {{ $transaction->department?->name ?? 'Departemen' }} &bull;
                    Vendor: <strong>{{ $transaction->vendor?->name ?? $transaction->owner?->name ?? '-' }}</strong>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @include('invoice-verification.components.status-badge', ['value' => $transaction->status])
                <span class="badge {{ $sheet->status->badgeClass() }}">{{ $sheet->status->value }}</span>
                <a href="{{ route('invoice-verification.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-secondary">
                    <iconify-icon icon="solar:arrow-left-outline" class="me-1"></iconify-icon>Kembali ke Transaksi
                </a>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('invoice-verification.transactions.ppa-verification-sheets.update', $transaction) }}" id="ppa-edit-form">
    @csrf
    @method('PUT')

    {{-- ==================== SECTION 1: LEMBAR 1 ==================== --}}
    <div class="card ppa-form-card">
        <div class="ppa-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary">Lembar 1</span>
                    <h5 class="mb-0 fw-bold">Pengajuan Penggunaan Anggaran</h5>
                </div>
                <div class="text-muted small mt-1">Sesuai format resmi <strong>LRTJ-FR-KDA-001 Rev. 01</strong></div>
            </div>
            <span class="ppa-doc-badge">LRTJ-FR-KDA-001</span>
        </div>
        <div class="card-body p-4">
            <!-- Uraian Transaksi -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Uraian / Deskripsi Pengajuan <span class="text-danger">*</span></label>
                <textarea
                    name="description"
                    class="form-control @error('description') is-invalid @enderror"
                    rows="2"
                    placeholder="Contoh: Konsumsi Kegiatan Koordinasi Tim Operasional"
                    required
                >{{ old('description', $transaction->invoiceMetadata?->description ?? $transaction->description) }}</textarea>
                <div class="form-text">Uraian ini akan tercetak pada formulir pengajuan penggunaan anggaran.</div>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Rincian Anggaran Biaya (RAB) Table -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Rincian Anggaran Biaya (RAB)</h6>
                        <small class="text-muted">Pilih atau ketik kode akun anggaran (RAB) & nama kegiatan dari Master Budget, serta nilai realisasi setelah PPN.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-info d-inline-flex align-items-center gap-1" id="btn-browse-budget" title="Buka katalog Master Budget Plan Codes">
                            <iconify-icon icon="solar:database-outline"></iconify-icon>
                            <span>Master Budget</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" id="btn-add-rab">
                            <iconify-icon icon="solar:add-circle-outline"></iconify-icon>
                            <span>Tambah Baris Kegiatan</span>
                        </button>
                    </div>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table table-ppa align-middle mb-0" id="rab-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;" class="text-center">No</th>
                                <th style="width: 270px;">Kode Anggaran (RAB) <span class="text-danger">*</span></th>
                                <th>Nama Kegiatan <span class="text-danger">*</span></th>
                                <th style="width: 240px;" class="text-end">Nilai Realisasi (Setelah PPN) <span class="text-danger">*</span></th>
                                <th style="width: 50px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="rab-tbody">
                            @foreach ($budgetItems as $idx => $item)
                                <tr class="rab-row">
                                    <td class="text-center fw-bold rab-row-no">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input
                                                type="text"
                                                name="budget_items[{{ $idx }}][kode_rab]"
                                                class="form-control form-control-sm rab-kode @error("budget_items.$idx.kode_rab") is-invalid @enderror"
                                                value="{{ $item['kode_rab'] ?? '' }}"
                                                placeholder="Contoh: 01.S02.001"
                                                autocomplete="off"
                                                required
                                            >
                                            <button type="button" class="btn btn-outline-primary btn-search-rab" title="Cari di Master Budget Plan Code">
                                                <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            name="budget_items[{{ $idx }}][nama_kegiatan]"
                                            class="form-control form-control-sm rab-nama @error("budget_items.$idx.nama_kegiatan") is-invalid @enderror"
                                            value="{{ $item['nama_kegiatan'] ?? '' }}"
                                            placeholder="Nama kegiatan / keperluan"
                                            autocomplete="off"
                                            required
                                        >
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Rp</span>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                name="budget_items[{{ $idx }}][nilai_realisasi]"
                                                class="form-control form-control-sm text-end rab-nilai @error("budget_items.$idx.nilai_realisasi") is-invalid @enderror"
                                                value="{{ $item['nilai_realisasi'] ?? 0 }}"
                                                required
                                            >
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger p-1 btn-del-rab" title="Hapus Baris" {{ count($budgetItems) <= 1 ? 'disabled' : '' }}>
                                            <iconify-icon icon="solar:trash-bin-trash-outline"></iconify-icon>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end fw-bold">TOTAL REALISASI:</th>
                                <th class="text-end fw-bold text-primary fs-6" id="rab-total-display">
                                    Rp {{ number_format($totalRealization, 0, ',', '.') }}
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @error('budget_items')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Financial Details & Terbilang -->
            <div class="row g-3 p-3 bg-light rounded border">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Uang Muka / Termin yang telah dibayar</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="advance_payment_value"
                            class="form-control"
                            value="{{ $advancePayment }}"
                            placeholder="0"
                        >
                    </div>
                    <div class="form-text">Kosongkan atau isi 0 bila tidak ada uang muka.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nilai Kurs</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input
                            type="number"
                            step="0.0001"
                            min="0"
                            name="exchange_rate_value"
                            class="form-control"
                            value="{{ $exchangeRate }}"
                            placeholder="1.00"
                        >
                    </div>
                    <div class="form-text">Default: 1.00 untuk transaksi mata uang IDR.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== SECTION 2: LEMBAR 2 ==================== --}}
    <div class="card ppa-form-card">
        <div class="ppa-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary">Lembar 2</span>
                    <h5 class="mb-0 fw-bold">Lembar Verifikasi Check List - Dokumen Pendukung Pembayaran</h5>
                </div>
                <div class="text-muted small mt-1">Sesuai format resmi <strong>LRTJ-FR-KDA-004 Rev. 04</strong></div>
            </div>
            <span class="ppa-doc-badge">LRTJ-FR-KDA-004</span>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-info d-flex align-items-center gap-2 py-2 mb-3">
                <iconify-icon icon="solar:info-circle-outline" class="fs-4"></iconify-icon>
                <div class="small">
                    Pilih status <strong>Dibutuhkan (Ya / Tidak)</strong> dan <strong>Aktual (Ada / Tidak Ada)</strong> untuk setiap item dokumen.
                    Kolom <strong>Keterangan</strong> otomatis terisi nomor dokumen yang sudah diunggah, atau dapat Anda edit manual.
                </div>
            </div>

            <div class="table-responsive border rounded">
                <table class="table table-ppa align-middle mb-0">
                    <thead>
                        <tr>
                            <th colspan="2" style="width: 70px;" class="text-center">No</th>
                            <th>Kelengkapan Dokumen</th>
                            <th style="width: 140px;" class="text-center">Dibutuhkan</th>
                            <th style="width: 140px;" class="text-center">Aktual</th>
                            <th style="width: 280px;">Keterangan (Nomor Dokumen)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($checklistCategories as $cat)
                            <tr class="category-row">
                                <td class="text-center fw-bold" style="width: 32px;">{{ $cat['roman'] }}</td>
                                <td colspan="5">{{ $cat['title'] }}</td>
                            </tr>
                            @foreach ($cat['items'] as $item)
                                @php
                                    $key = $item['key'];
                                    $isRequired = old("checklist_items.$key.is_required", $item['is_required'] ? '1' : '0');
                                    $isActual = old("checklist_items.$key.is_actual", $item['is_actual'] ? '1' : '0');
                                    $notes = old("checklist_items.$key.notes", $item['notes'] ?? '');
                                @endphp
                                <tr>
                                    <td style="width: 32px;"></td>
                                    <td class="text-center text-muted fw-semibold" style="width: 38px;">{{ $item['no'] }}</td>
                                    <td style="{{ !empty($item['is_sub']) ? 'padding-left: 24px;' : '' }}">
                                        <div class="fw-semibold text-dark">{{ $item['name'] }}</div>
                                    </td>
                                    <td class="text-center">
                                        <select class="form-select form-select-sm" name="checklist_items[{{ $key }}][is_required]">
                                            <option value="1" @selected($isRequired == '1' || $isRequired === true)>Ya</option>
                                            <option value="0" @selected($isRequired == '0' || $isRequired === false)>Tidak</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <select class="form-select form-select-sm" name="checklist_items[{{ $key }}][is_actual]">
                                            <option value="1" @selected($isActual == '1' || $isActual === true)>Ada</option>
                                            <option value="0" @selected($isActual == '0' || $isActual === false)>Tidak Ada</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            name="checklist_items[{{ $key }}][notes]"
                                            value="{{ $notes }}"
                                            placeholder="Nomor dokumen / keterangan..."
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sticky Bottom Action Bar -->
    <div class="sticky-action-bar d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
            <button type="submit" name="action" value="save" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                <iconify-icon icon="solar:diskette-outline"></iconify-icon>
                <span>Simpan Perubahan (Draft)</span>
            </button>
            <a href="{{ route('invoice-verification.transactions.ppa-verification-sheets.preview', $transaction) }}" target="_blank" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                <iconify-icon icon="solar:eye-outline"></iconify-icon>
                <span>Preview PDF (2 Lembar)</span>
            </a>
            <a href="{{ route('invoice-verification.transactions.ppa-verification-sheets.download', $transaction) }}" class="btn btn-outline-success d-inline-flex align-items-center gap-1">
                <iconify-icon icon="solar:download-outline"></iconify-icon>
                <span>Download PDF</span>
            </a>
        </div>

        <div>
            @if ($sheet->status === \App\Modules\InvoiceVerification\Domain\Enums\PpaVerificationSheetStatus::SUBMITTED)
                <span class="badge bg-success-subtle text-success border border-success p-2">
                    <iconify-icon icon="solar:check-circle-bold" class="me-1"></iconify-icon>Sudah disubmit ke Approval Kadep & Kadiv
                </span>
            @else
                <button
                    type="submit"
                    name="action"
                    value="submit"
                    class="btn btn-primary d-inline-flex align-items-center gap-1"
                    data-swal-confirm
                    data-confirm-title="Submit Pengajuan?"
                    data-confirm-message="Apakah Anda yakin data RAB & Checklist sudah benar? Pengajuan akan diteruskan ke Kepala Departemen dan Kepala Divisi."
                    data-confirm-button="Ya, submit"
                >
                    <iconify-icon icon="solar:plain-2-outline"></iconify-icon>
                    <span>Simpan & Submit ke Kadep & Kadiv</span>
                </button>
            @endif
        </div>
    </div>
</form>

<!-- Modal Search Master Budget Plan Codes -->
<div class="modal fade" id="modalBudgetSearch" tabindex="-1" aria-labelledby="modalBudgetSearchLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="modalBudgetSearchLabel">
                        <iconify-icon icon="solar:database-outline" class="me-1 text-primary"></iconify-icon>
                        Cari Master Budget Plan Code (Versus)
                    </h5>
                    <p class="text-muted small mb-0">Pilih Kode RAB dan Nama Kegiatan resmi dari Master Budget Plan Codes LRT Jakarta.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <span class="input-group-text"><iconify-icon icon="solar:magnifer-outline"></iconify-icon></span>
                    <input
                        type="text"
                        id="modal-budget-query"
                        class="form-control"
                        placeholder="Ketik kata kunci kode RAB, nama kegiatan, kode subsidi, atau departemen..."
                        autocomplete="off"
                    >
                    <button class="btn btn-outline-secondary" type="button" id="btn-modal-budget-clear" title="Bersihkan">
                        <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                    </button>
                    <button class="btn btn-primary" type="button" id="btn-modal-budget-search">Cari</button>
                </div>

                <div id="modal-budget-loading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                    <div class="small text-muted mt-2">Mengambil data Master Budget dari server Versus...</div>
                </div>

                <div id="modal-budget-empty" class="text-center py-4 d-none text-muted">
                    <iconify-icon icon="solar:box-minimalistic-outline" class="fs-1 text-muted"></iconify-icon>
                    <p class="mt-2 mb-0">Data tidak ditemukan. Coba gunakan kata kunci pencarian yang lain.</p>
                </div>

                <div class="table-responsive" id="modal-budget-table-wrap">
                    <table class="table table-sm table-hover align-middle mb-0" id="modal-budget-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 140px;">Kode RAB</th>
                                <th>Nama Kegiatan</th>
                                <th style="width: 90px;">Dept</th>
                                <th style="width: 100px;">Subsidi</th>
                                <th style="width: 80px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="modal-budget-tbody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <small class="text-muted"><iconify-icon icon="solar:info-circle-outline" class="me-1"></iconify-icon>Klik tombol <strong>Pilih</strong> untuk mengisi otomatis baris RAB.</small>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('rab-tbody');
    const btnAdd = document.getElementById('btn-add-rab');
    const btnBrowseBudget = document.getElementById('btn-browse-budget');
    const totalDisplay = document.getElementById('rab-total-display');
    const searchUrl = "{{ route('invoice-verification.budget-plan-codes.search') }}";

    let activeTargetRow = null;
    let searchDebounceTimer = null;
    let modalDebounceTimer = null;

    // Bootstrap Modal instance
    const modalEl = document.getElementById('modalBudgetSearch');
    const budgetModal = modalEl && window.bootstrap ? new window.bootstrap.Modal(modalEl) : null;
    const modalQueryInput = document.getElementById('modal-budget-query');
    const modalSearchBtn = document.getElementById('btn-modal-budget-search');
    const modalClearBtn = document.getElementById('btn-modal-budget-clear');
    const modalTbody = document.getElementById('modal-budget-tbody');
    const modalLoading = document.getElementById('modal-budget-loading');
    const modalEmpty = document.getElementById('modal-budget-empty');
    const modalTableWrap = document.getElementById('modal-budget-table-wrap');

    // Global Autocomplete Dropdown attached to body
    let globalDropdown = document.getElementById('global-budget-dropdown');
    if (!globalDropdown) {
        globalDropdown = document.createElement('div');
        globalDropdown.id = 'global-budget-dropdown';
        globalDropdown.className = 'budget-autocomplete-dropdown d-none';
        document.body.appendChild(globalDropdown);
    }
    let activeInputForDropdown = null;

    function formatIdr(val) {
        return 'Rp ' + Math.round(val).toLocaleString('id-ID');
    }

    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.rab-nilai').forEach(function (input) {
            const val = parseFloat(input.value) || 0;
            total += val;
        });
        totalDisplay.textContent = formatIdr(total);
    }

    function reindexRows() {
        const rows = tbody.querySelectorAll('.rab-row');
        rows.forEach(function (row, idx) {
            row.querySelector('.rab-row-no').textContent = idx + 1;
            row.querySelector('.rab-kode').name = `budget_items[${idx}][kode_rab]`;
            row.querySelector('.rab-nama').name = `budget_items[${idx}][nama_kegiatan]`;
            row.querySelector('.rab-nilai').name = `budget_items[${idx}][nilai_realisasi]`;

            const delBtn = row.querySelector('.btn-del-rab');
            if (delBtn) {
                delBtn.disabled = (rows.length <= 1);
            }
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function highlightRow(row) {
        const kodeInput = row.querySelector('.rab-kode');
        const namaInput = row.querySelector('.rab-nama');
        if (kodeInput) {
            kodeInput.classList.remove('highlight-success-field');
            void kodeInput.offsetWidth;
            kodeInput.classList.add('highlight-success-field');
        }
        if (namaInput) {
            namaInput.classList.remove('highlight-success-field');
            void namaInput.offsetWidth;
            namaInput.classList.add('highlight-success-field');
        }
    }

    function applyBudgetItemToRow(row, rabCode, codeName) {
        if (!row) return;
        const kodeInput = row.querySelector('.rab-kode');
        const namaInput = row.querySelector('.rab-nama');
        if (kodeInput) kodeInput.value = rabCode || '';
        if (namaInput) namaInput.value = codeName || '';
        highlightRow(row);
        hideAutocomplete();
    }

    // ==================== AUTOCOMPLETE DROPDOWN ====================
    function hideAutocomplete() {
        globalDropdown.classList.add('d-none');
        globalDropdown.innerHTML = '';
        activeInputForDropdown = null;
    }

    function showAutocomplete(inputEl, items) {
        if (!items || items.length === 0) {
            hideAutocomplete();
            return;
        }

        const rect = inputEl.getBoundingClientRect();
        globalDropdown.style.top = (rect.bottom + window.scrollY) + 'px';
        globalDropdown.style.left = (rect.left + window.scrollX) + 'px';
        globalDropdown.style.width = Math.max(rect.width, 360) + 'px';

        let html = '';
        items.slice(0, 15).forEach(item => {
            html += `
                <div class="budget-autocomplete-item" data-code="${escapeHtml(item.rab_code)}" data-name="${escapeHtml(item.code_name)}">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong class="text-primary">${escapeHtml(item.rab_code)}</strong>
                        ${item.department_code && item.department_code !== 'NONE' ? `<span class="badge bg-light text-dark border">${escapeHtml(item.department_code)}</span>` : ''}
                    </div>
                    <div class="text-dark small text-truncate mt-1">${escapeHtml(item.code_name)}</div>
                    ${item.kode_subsidi ? `<div class="text-muted" style="font-size:0.72rem;">Subsidi: ${escapeHtml(item.kode_subsidi)} &bull; ${escapeHtml(item.category || '')}</div>` : ''}
                </div>
            `;
        });

        globalDropdown.innerHTML = html;
        globalDropdown.classList.remove('d-none');
        activeInputForDropdown = inputEl;
    }

    globalDropdown.addEventListener('click', function (e) {
        const itemEl = e.target.closest('.budget-autocomplete-item');
        if (!itemEl || !activeInputForDropdown) return;

        const row = activeInputForDropdown.closest('.rab-row');
        const rabCode = itemEl.getAttribute('data-code');
        const codeName = itemEl.getAttribute('data-name');
        applyBudgetItemToRow(row, rabCode, codeName);
    });

    document.addEventListener('click', function (e) {
        if (!globalDropdown.contains(e.target) && e.target !== activeInputForDropdown) {
            hideAutocomplete();
        }
    });

    window.addEventListener('resize', hideAutocomplete);
    window.addEventListener('scroll', hideAutocomplete, true);

    function triggerAutocomplete(inputEl) {
        const q = inputEl.value.trim();
        if (q.length < 2) {
            hideAutocomplete();
            return;
        }

        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            fetch(`${searchUrl}?q=${encodeURIComponent(q)}&per_page=15`)
                .then(res => res.json())
                .then(data => {
                    if (document.activeElement === inputEl) {
                        showAutocomplete(inputEl, data.data || []);
                    }
                })
                .catch(() => hideAutocomplete());
        }, 250);
    }

    // ==================== MODAL SEARCH ====================
    function openBudgetModal(row) {
        activeTargetRow = row || tbody.querySelector('.rab-row:last-child');
        const initialQuery = activeTargetRow ? (activeTargetRow.querySelector('.rab-kode')?.value || activeTargetRow.querySelector('.rab-nama')?.value || '') : '';
        modalQueryInput.value = initialQuery;
        if (budgetModal) {
            budgetModal.show();
            setTimeout(() => {
                modalQueryInput.focus();
                searchBudgetModal(initialQuery);
            }, 300);
        }
    }

    function searchBudgetModal(query) {
        modalLoading.classList.remove('d-none');
        modalEmpty.classList.add('d-none');
        modalTableWrap.classList.add('d-none');
        modalTbody.innerHTML = '';

        fetch(`${searchUrl}?q=${encodeURIComponent(query || '')}&per_page=50`)
            .then(res => res.json())
            .then(resData => {
                modalLoading.classList.add('d-none');
                const items = resData.data || [];
                if (items.length === 0) {
                    modalEmpty.classList.remove('d-none');
                    return;
                }

                modalTableWrap.classList.remove('d-none');
                let rowsHtml = '';
                items.forEach(item => {
                    rowsHtml += `
                        <tr>
                            <td><strong class="text-primary font-monospace">${escapeHtml(item.rab_code)}</strong></td>
                            <td class="small fw-semibold text-dark">${escapeHtml(item.code_name)}</td>
                            <td><span class="badge bg-light text-dark border">${escapeHtml(item.department_code || '-')}</span></td>
                            <td class="small text-muted font-monospace">${escapeHtml(item.kode_subsidi || '-')}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-primary btn-choose-budget py-1 px-2"
                                    data-code="${escapeHtml(item.rab_code)}"
                                    data-name="${escapeHtml(item.code_name)}">
                                    Pilih
                                </button>
                            </td>
                        </tr>
                    `;
                });
                modalTbody.innerHTML = rowsHtml;
            })
            .catch(err => {
                modalLoading.classList.add('d-none');
                modalEmpty.classList.remove('d-none');
            });
    }

    if (modalSearchBtn) {
        modalSearchBtn.addEventListener('click', () => searchBudgetModal(modalQueryInput.value.trim()));
    }

    if (modalClearBtn) {
        modalClearBtn.addEventListener('click', () => {
            modalQueryInput.value = '';
            modalQueryInput.focus();
            searchBudgetModal('');
        });
    }

    if (modalQueryInput) {
        modalQueryInput.addEventListener('input', () => {
            clearTimeout(modalDebounceTimer);
            modalDebounceTimer = setTimeout(() => {
                searchBudgetModal(modalQueryInput.value.trim());
            }, 300);
        });

        modalQueryInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchBudgetModal(modalQueryInput.value.trim());
            }
        });
    }

    if (modalTbody) {
        modalTbody.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-choose-budget');
            if (!btn) return;

            const rabCode = btn.getAttribute('data-code');
            const codeName = btn.getAttribute('data-name');

            if (!activeTargetRow) {
                btnAdd.click();
                activeTargetRow = tbody.querySelector('.rab-row:last-child');
            }

            applyBudgetItemToRow(activeTargetRow, rabCode, codeName);
            if (budgetModal) budgetModal.hide();
        });
    }

    if (btnBrowseBudget) {
        btnBrowseBudget.addEventListener('click', () => openBudgetModal(null));
    }

    // ==================== TABLE EVENTS ====================
    btnAdd.addEventListener('click', function () {
        const rowCount = tbody.querySelectorAll('.rab-row').length;
        const tr = document.createElement('tr');
        tr.className = 'rab-row';
        tr.innerHTML = `
            <td class="text-center fw-bold rab-row-no">${rowCount + 1}</td>
            <td>
                <div class="input-group input-group-sm">
                    <input
                        type="text"
                        name="budget_items[${rowCount}][kode_rab]"
                        class="form-control form-control-sm rab-kode"
                        placeholder="Contoh: 01.S02.001"
                        autocomplete="off"
                        required
                    >
                    <button type="button" class="btn btn-outline-primary btn-search-rab" title="Cari di Master Budget Plan Code">
                        <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
                    </button>
                </div>
            </td>
            <td>
                <input
                    type="text"
                    name="budget_items[${rowCount}][nama_kegiatan]"
                    class="form-control form-control-sm rab-nama"
                    placeholder="Nama kegiatan / keperluan"
                    autocomplete="off"
                    required
                >
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">Rp</span>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="budget_items[${rowCount}][nilai_realisasi]"
                        class="form-control form-control-sm text-end rab-nilai"
                        value="0"
                        required
                    >
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger p-1 btn-del-rab" title="Hapus Baris">
                    <iconify-icon icon="solar:trash-bin-trash-outline"></iconify-icon>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        reindexRows();
        updateTotal();

        const newKode = tr.querySelector('.rab-kode');
        if (newKode) newKode.focus();
    });

    tbody.addEventListener('click', function (e) {
        const delBtn = e.target.closest('.btn-del-rab');
        if (delBtn) {
            const rows = tbody.querySelectorAll('.rab-row');
            if (rows.length > 1) {
                delBtn.closest('.rab-row').remove();
                reindexRows();
                updateTotal();
            }
            return;
        }

        const searchBtn = e.target.closest('.btn-search-rab');
        if (searchBtn) {
            const row = searchBtn.closest('.rab-row');
            openBudgetModal(row);
        }
    });

    tbody.addEventListener('input', function (e) {
        if (e.target.classList.contains('rab-nilai')) {
            updateTotal();
        } else if (e.target.classList.contains('rab-kode') || e.target.classList.contains('rab-nama')) {
            triggerAutocomplete(e.target);
        }
    });

    tbody.addEventListener('focusin', function (e) {
        if (e.target.classList.contains('rab-kode') || e.target.classList.contains('rab-nama')) {
            if (e.target.value.trim().length >= 2) {
                triggerAutocomplete(e.target);
            }
        }
    });

    updateTotal();
});
</script>

@else

{{-- Contract PPA fallback view --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ $transaction->registration_number }}</h5>
            <p class="text-muted mb-0">Checklist terstruktur untuk membandingkan kelengkapan dokumen PPA dengan upload aktual.</p>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('invoice-verification.transactions.ppa-verification-sheets.submit', $transaction) }}">
                @csrf
                <button class="btn btn-primary">Submit Checklist</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('invoice-verification.transactions.ppa-verification-sheets.update', $transaction) }}">
            @csrf
            @method('PUT')

            <div class="table-responsive">
                <table class="table table-centered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Dokumen</th>
                            <th>Attachment Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sheet->items as $index => $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->documentType?->name }}</div>
                                    <input type="hidden" name="items[{{ $index }}][document_type_id]" value="{{ $item->document_type_id }}">
                                </td>
                                <td>
                                    <select class="form-select" name="items[{{ $index }}][attachment_status]">
                                        <option value="ATTACHED" @selected(($item->attachment_status?->value ?? null) === 'ATTACHED')>ATTACHED</option>
                                        <option value="NOT_ATTACHED" @selected(($item->attachment_status?->value ?? null) === 'NOT_ATTACHED')>NOT_ATTACHED</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="items[{{ $index }}][notes]" value="{{ $item->notes }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div>
                    @include('invoice-verification.components.status-badge', ['value' => $sheet->status])
                    @if ($sheet->rejection_notes)
                        <p class="text-danger mt-2 mb-0">Catatan penolakan: {{ $sheet->rejection_notes }}</p>
                    @endif
                </div>
                <button class="btn btn-outline-primary">Simpan Checklist</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
