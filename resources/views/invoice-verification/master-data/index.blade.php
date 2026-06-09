@extends('layouts.vertical', ['subtitle' => 'Master Data'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Master Data', 'subtitle' => 'Reference Tables'])
@include('invoice-verification.partials.flash')

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">LDAP Sync Ready</h5>
                <form method="POST" action="{{ route('invoice-verification.master-data.ldap-sync') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary">Sync Placeholder</button>
                </form>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">Users, departments, dan divisions sudah disiapkan untuk sinkronisasi LDAP melalui service contract.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Tambah Vendor</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('invoice-verification.master-data.vendors.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">Vendor Name</label>
                        <input class="form-control" name="name" value="{{ old('name') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">NPWP</label>
                        <input class="form-control" name="npwp" value="{{ old('npwp') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Bank Name</label>
                        <input class="form-control" name="bank_name" value="{{ old('bank_name') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nomor Rekening</label>
                        <input class="form-control" name="default_account_number" value="{{ old('default_account_number') }}">
                    </div>
                    <div class="col-12"><button class="btn btn-primary w-100">Simpan Vendor</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Reference Tables</h5></div>
            <div class="card-body">
                <ul class="nav nav-tabs nav-bordered mb-3">
                    <li class="nav-item"><a href="#vendors" data-bs-toggle="tab" class="nav-link active">Vendors</a></li>
                    <li class="nav-item"><a href="#memo" data-bs-toggle="tab" class="nav-link">Memo</a></li>
                    <li class="nav-item"><a href="#agreements" data-bs-toggle="tab" class="nav-link">Agreements</a></li>
                    <li class="nav-item"><a href="#templates" data-bs-toggle="tab" class="nav-link">Templates</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane show active" id="vendors">
                        <div class="table-responsive">
                            <table class="table table-centered mb-0">
                                <thead class="table-light"><tr><th>Vendor Name</th><th>NPWP</th><th>Bank Name</th><th>Nomor Rekening</th></tr></thead>
                                <tbody>@foreach($vendors as $vendor)<tr><td>{{ $vendor->name }}</td><td>{{ $vendor->npwp }}</td><td>{{ $vendor->defaultBank?->name ?? '-' }}</td><td>{{ $vendor->default_account_number ?? '-' }}</td></tr>@endforeach</tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="memo">
                        @can('createMemoRequest', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class)
                            <div class="mb-3">
                                <form method="POST" action="{{ route('invoice-verification.master-data.memo-requests.store') }}" class="row g-2" enctype="multipart/form-data">
                                    @csrf
                                    <div class="col-md-3">
                                        <label class="form-label">Nomor Memo</label>
                                        <input class="form-control" name="memo_number" placeholder="Masukkan nomor memo" value="{{ old('memo_number') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tanggal Memo</label>
                                        <input class="form-control" type="date" name="memo_date" value="{{ old('memo_date') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Perihal</label>
                                        <input class="form-control" name="subject" placeholder="Masukkan perihal memo" value="{{ old('subject') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Divisi</label>
                                        <select class="form-select" name="division_id">
                                            @foreach ($divisions as $division)
                                                <option value="{{ $division->id }}" @selected(old('division_id', auth()->user()?->division_id) === $division->id)>{{ $division->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Departemen</label>
                                        <select class="form-select" name="department_id">
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}" @selected(old('department_id', auth()->user()?->department_id) === $department->id)>{{ $department->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">File Memo</label>
                                        <input class="form-control" type="file" name="memo_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-primary w-100">Simpan Memo</button>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Keterangan Tambahan</label>
                                        <textarea class="form-control" rows="2" name="description" placeholder="Keterangan tambahan memo">{{ old('description') }}</textarea>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="alert alert-light border mb-3">
                                Form upload memo hanya tersedia untuk Admin Divisi. Akuntansi tetap dapat melihat daftar memo yang sudah terdaftar.
                            </div>
                        @endcan
                        <div class="table-responsive">
                            <table class="table table-centered mb-0">
                                <thead class="table-light"><tr><th>Memo Number</th><th>Date</th><th>Subject</th><th>File</th><th>Uploader</th></tr></thead>
                                <tbody>@foreach($memoRequests as $memo)<tr><td>{{ $memo->memo_number }}</td><td>{{ $memo->memo_date?->format('d M Y') }}</td><td>{{ $memo->subject }}</td><td>@if($memo->file_path)<a href="{{ route('invoice-verification.master-data.memo-requests.download', $memo) }}" class="btn btn-sm btn-outline-primary">{{ $memo->file_name ?? 'Download' }}</a>@else<span class="text-muted">Belum ada file</span>@endif</td><td>{{ $memo->creator?->name ?? '-' }}</td></tr>@endforeach</tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="agreements">
                        @can('createAgreementReference', \App\Modules\InvoiceVerification\Domain\Models\Transaction::class)
                            <div class="mb-3">
                                <form method="POST" action="{{ route('invoice-verification.master-data.agreement-references.store') }}" class="row g-2" enctype="multipart/form-data">
                                    @csrf
                                    <div class="col-md-3">
                                        <label class="form-label">Vendor</label>
                                        <select class="form-select" name="vendor_id">
                                            <option value="">Pilih vendor</option>
                                            @foreach ($vendors as $vendor)
                                                <option value="{{ $vendor->id }}" @selected(old('vendor_id') === $vendor->id)>{{ $vendor->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Nomor Kontrak</label>
                                        <input class="form-control" name="contract_number" value="{{ old('contract_number') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Nilai Kontrak</label>
                                        <input class="form-control" name="contract_value" value="{{ old('contract_value') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tanggal Berlaku</label>
                                        <input class="form-control" type="date" name="effective_date" value="{{ old('effective_date') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tanggal Berakhir</label>
                                        <input class="form-control" type="date" name="expired_at" value="{{ old('expired_at') }}">
                                    </div>
                                    <div class="col-md-9">
                                        <label class="form-label">File Agreement</label>
                                        <input class="form-control" type="file" name="agreement_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                    </div>
                                    <div class="col-md-3"><button class="btn btn-primary w-100">Simpan Kontrak</button></div>
                                </form>
                            </div>
                        @else
                            <div class="alert alert-light border mb-3">
                                Form upload kontrak hanya tersedia untuk Admin Divisi. Data tetap dapat dipilih ulang pada transaksi berikutnya.
                            </div>
                        @endcan
                        <div class="table-responsive">
                            <table class="table table-centered mb-0">
                                <thead class="table-light"><tr><th>Nomor Kontrak</th><th>Vendor</th><th>Nilai Kontrak</th><th>File</th><th>Unit</th></tr></thead>
                                <tbody>@foreach($agreementReferences as $agreement)<tr><td>{{ $agreement->contract_number }}</td><td>{{ $agreement->vendor?->name ?? '-' }}</td><td>{{ number_format((float) $agreement->contract_value, 2, ',', '.') }}</td><td>@if($agreement->file_path)<a href="{{ route('invoice-verification.master-data.agreement-references.download', $agreement) }}" class="btn btn-sm btn-outline-primary">{{ $agreement->file_name ?? 'Download' }}</a>@else<span class="text-muted">Belum ada file</span>@endif</td><td>{{ $agreement->division?->name ?? '-' }} / {{ $agreement->department?->name ?? '-' }}</td></tr>@endforeach</tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="templates">
                        <div class="mb-3">
                            <form method="POST" action="{{ route('invoice-verification.master-data.template-references.store') }}" class="row g-2">
                                @csrf
                                <div class="col-md-3">
                                    <label class="form-label">Template Code</label>
                                    <input class="form-control" name="code" placeholder="Masukkan kode template">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Template Name</label>
                                    <input class="form-control" name="name" placeholder="Masukkan nama template">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Template Type</label>
                                    <select class="form-select" name="template_type">
                                        <option value="GENERATED_DOCUMENT">Generated Document</option>
                                        <option value="FINAL_COMPILATION_ORDER">Final Compilation Order</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Transaction Type</label>
                                    <select class="form-select" name="transaction_type_id">
                                        <option value="">Type</option>
                                        @foreach ($transactionTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1"><button class="btn btn-primary w-100">+</button></div>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-centered mb-0">
                                <thead class="table-light"><tr><th>Code</th><th>Name</th><th>Type</th><th>Document Code</th></tr></thead>
                                <tbody>@foreach($templateReferences as $template)<tr><td>{{ $template->code }}</td><td>{{ $template->name }}</td><td>{{ $template->template_type->value }}</td><td>{{ $template->document_code ?? '-' }}</td></tr>@endforeach</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
