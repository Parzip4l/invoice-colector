<p>Halo,</p>

<p>
    Dokumen transaksi {{ $transaction->registration_number }} untuk
    {{ $transaction->transactionType?->name ?? 'transaksi' }} telah dinyatakan lengkap.
</p>

<p>Status transaksi saat ini: <strong>Received</strong>.</p>

<p>Transaksi akan dilanjutkan ke proses pembayaran oleh Finance.</p>
