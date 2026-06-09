@extends('layouts.vertical', ['subtitle' => 'Finance Queue'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Finance Queue', 'subtitle' => 'Register, Numbering, dan Arsip Final'])
@include('invoice-verification.partials.flash')

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-1">Transaksi Siap Diproses Finance</h5>
        <p class="text-muted mb-0">Dokumen sudah lolos verifikasi akuntansi, register sudah dibuat, dan bundle final siap ditutup oleh finance.</p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transaksi</th>
                        <th>Vendor</th>
                        <th>Register</th>
                        <th>Compiled File</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $transaction->registration_number }}</div>
                                <div class="text-muted small">{{ $transaction->title }}</div>
                            </td>
                            <td>{{ $transaction->vendor?->name ?? '-' }}</td>
                            <td>{{ $transaction->numberingRegister?->register_number ?? '-' }}</td>
                            <td>{{ $transaction->compiledDocument?->compiled_file_name ?? '-' }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('invoice-verification.finance.update', $transaction) }}" class="d-inline-flex">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="notes" value="Finance menyelesaikan register, numbering, dan arsip final sesuai workflow pembayaran.">
                                    <button class="btn btn-sm btn-success">Selesaikan Finance</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Tidak ada transaksi yang sedang menunggu proses finance.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
