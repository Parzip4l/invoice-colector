<p>Halo,</p>

<p>
    Dokumen transaksi {{ $transaction->registration_number }} untuk
    {{ $transaction->transactionType?->name ?? 'transaksi' }} telah dinyatakan lengkap dan disetujui.
</p>

<p>Status transaksi saat ini: <strong>{{ $transaction->status?->label() ?? 'Selesai' }}</strong>.</p>

