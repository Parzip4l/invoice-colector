@extends('layouts.vertical', ['subtitle' => 'Numbering Register'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Finalization', 'subtitle' => 'Numbering Register'])
@include('invoice-verification.partials.flash')

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Register Number</th>
                        <th>Vendor</th>
                        <th>Invoice</th>
                        <th>Memo</th>
                        <th>Nilai Invoice</th>
                        <th>Generated</th>
                        <th class="text-end">Edit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registers as $register)
                        <tr>
                            <td>{{ $register->register_number }}</td>
                            <td>{{ $register->vendor_name }}</td>
                            <td>{{ $register->invoice_number }}</td>
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
                                        <input class="form-control" name="account_number" value="{{ old('account_number', $register->account_number) }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Nilai Invoice</label>
                                        <input type="number" step="0.01" class="form-control" name="invoice_value" value="{{ old('invoice_value', $register->invoice_value) }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">PPN</label>
                                        <input type="number" step="0.01" class="form-control" name="ppn_value" value="{{ old('ppn_value', $register->ppn_value) }}">
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

        <div class="mt-3">
            {{ $registers->links() }}
        </div>
    </div>
</div>
@endsection
