@extends('layouts.vertical', ['subtitle' => 'Panduan Aplikasi'])

@section('content')
@php
    $roleGuides = [
        [
            'role' => 'Admin Divisi',
            'badge' => 'ADMIN_DIVISI',
            'icon' => 'solar:shield-user-outline',
            'summary' => 'Mengelola master data, memonitor transaksi divisi, membuat dokumen administrasi, dan menjaga referensi agar workflow berjalan.',
            'can' => [
                'Mengelola vendor, organisasi, LDAP whitelist, memo, agreement, dan template dokumen.',
                'Melihat seluruh transaksi yang relevan untuk proses verifikasi.',
                'Generate dokumen administrasi saat approval Kepala Departemen dan Kepala Divisi selesai.',
                'Mengelola numbering register, dokumen kompilasi, arsip, dan log audit.',
            ],
            'steps' => [
                'Buka Master Data untuk menambah atau memperbaiki referensi vendor, organisasi, LDAP user, memo, agreement, dan template.',
                'Gunakan Import E-Proc untuk memasukkan vendor atau list purchasing saat API E-Proc belum tersedia.',
                'Pantau Daftar Transaksi, lalu buka detail transaksi yang membutuhkan dokumen administrasi.',
                'Generate dokumen yang diperlukan, cek hasil preview, lalu lanjutkan proses sampai dokumen masuk kompilasi atau arsip.',
            ],
        ],
        [
            'role' => 'User Divisi',
            'badge' => 'USER_DIVISI',
            'icon' => 'solar:user-id-outline',
            'summary' => 'Membuat transaksi internal selain PPA vendor, mengunggah dokumen awal, dan memperbaiki transaksi jika dikembalikan.',
            'can' => [
                'Membuat transaksi internal sesuai divisi dan departemen sendiri.',
                'Upload dokumen transaksi saat status Draft atau Not Approved.',
                'Melihat transaksi yang dibuat sendiri.',
                'Submit ulang transaksi setelah revisi dokumen selesai.',
            ],
            'steps' => [
                'Masuk ke Transaksi, pilih Buat Transaksi, lalu isi jenis transaksi, vendor, nilai, divisi, departemen, dan informasi tagihan.',
                'Upload dokumen yang diwajibkan pada halaman dokumen transaksi.',
                'Submit transaksi agar masuk ke proses verifikasi Accounting.',
                'Jika transaksi dikembalikan, buka daftar Revisi, perbaiki dokumen atau metadata, lalu submit kembali.',
            ],
        ],
        [
            'role' => 'Vendor',
            'badge' => 'VENDOR',
            'icon' => 'solar:shop-outline',
            'summary' => 'Membuat transaksi PPA, mengunggah invoice dan dokumen vendor, lalu memperbaiki dokumen jika diminta.',
            'can' => [
                'Membuat transaksi PPA untuk vendor yang terhubung dengan akun login.',
                'Upload invoice, kwitansi, faktur pajak, BAPP, BAST, perjanjian, dan lampiran pekerjaan sesuai kebutuhan transaksi.',
                'Melihat transaksi milik vendor sendiri.',
                'Melakukan revisi saat dokumen tidak disetujui.',
            ],
            'steps' => [
                'Buka Transaksi, pilih Buat Transaksi, lalu isi data tagihan dan referensi agreement jika tersedia.',
                'Upload seluruh dokumen vendor yang diminta sampai status dokumen lengkap.',
                'Submit transaksi untuk dikirim ke Accounting.',
                'Pantau status transaksi. Jika ada revisi, buka menu Revisi, ganti dokumen yang bermasalah, lalu submit ulang.',
            ],
        ],
        [
            'role' => 'Akuntansi',
            'badge' => 'AKUNTANSI',
            'icon' => 'solar:calculator-outline',
            'summary' => 'Memeriksa kelengkapan dan kesesuaian dokumen transaksi sebelum diteruskan ke Finance.',
            'can' => [
                'Melihat antrean transaksi yang sudah disubmit.',
                'Memulai verifikasi Accounting.',
                'Menyatakan dokumen lengkap dan meneruskan transaksi ke Finance.',
                'Mengembalikan transaksi ke pembuat atau vendor jika perlu revisi.',
            ],
            'steps' => [
                'Buka Transaksi atau Verifikasi Accounting untuk melihat transaksi yang menunggu pemeriksaan.',
                'Buka detail transaksi, cek metadata, nominal, vendor, dan preview dokumen yang diunggah.',
                'Jika lengkap, tandai transaksi diterima agar masuk ke Finance.',
                'Jika ada masalah, pilih Not Approved dan tulis catatan revisi yang jelas.',
            ],
        ],
        [
            'role' => 'Finance',
            'badge' => 'FINANCE',
            'icon' => 'solar:wallet-money-outline',
            'summary' => 'Menjadwalkan pembayaran, mengunggah bukti bayar, dan menandai transaksi yang sudah dibayar.',
            'can' => [
                'Melihat transaksi yang sudah diterima Accounting.',
                'Mengisi jadwal pembayaran.',
                'Upload bukti pembayaran.',
                'Menandai transaksi sebagai Paid.',
            ],
            'steps' => [
                'Buka menu Finance untuk melihat transaksi yang siap diproses.',
                'Isi atau perbarui jadwal pembayaran sesuai rencana bayar.',
                'Upload bukti pembayaran setelah pembayaran dilakukan.',
                'Tandai Paid agar nilai transaksi dihitung sebagai sudah dibayar di dashboard.',
            ],
        ],
        [
            'role' => 'Kepala Departemen',
            'badge' => 'KEPALA_DEPARTEMEN',
            'icon' => 'solar:user-check-outline',
            'summary' => 'Memberikan approval tahap departemen untuk transaksi PPA atau review transaksi yang ditugaskan.',
            'can' => [
                'Melihat daftar approval yang ditugaskan.',
                'Menyetujui transaksi atau dokumen yang menunggu review Kepala Departemen.',
                'Menolak approval dengan catatan agar transaksi dikembalikan untuk revisi.',
            ],
            'steps' => [
                'Buka menu Approval atau Kadep Review.',
                'Periksa detail transaksi, nominal, vendor, dan dokumen pendukung.',
                'Pilih Approve jika sesuai.',
                'Pilih Reject jika perlu revisi, lalu isi catatan agar pihak pembuat memahami perbaikannya.',
            ],
        ],
        [
            'role' => 'Kepala Divisi',
            'badge' => 'KEPALA_DIVISI',
            'icon' => 'solar:user-check-rounded-outline',
            'summary' => 'Memberikan approval tahap divisi setelah approval Kepala Departemen selesai.',
            'can' => [
                'Melihat approval yang ditugaskan pada level divisi.',
                'Menyetujui transaksi setelah tahap Kepala Departemen selesai.',
                'Menolak approval dengan catatan untuk revisi.',
            ],
            'steps' => [
                'Buka menu Approval atau Kadiv Review.',
                'Cek detail transaksi, dokumen, dan catatan approval sebelumnya.',
                'Approve jika transaksi sudah sesuai kebijakan divisi.',
                'Reject dengan catatan jika transaksi perlu dikembalikan.',
            ],
        ],
    ];

    $workflow = [
        ['label' => 'Draft', 'text' => 'User Divisi atau Vendor membuat transaksi dan upload dokumen.'],
        ['label' => 'Submitted', 'text' => 'Transaksi dikirim untuk diperiksa Accounting.'],
        ['label' => 'In Review', 'text' => 'Accounting memeriksa dokumen dan metadata transaksi.'],
        ['label' => 'Received', 'text' => 'Dokumen lengkap dan diteruskan ke Finance.'],
        ['label' => 'Scheduled', 'text' => 'Finance menjadwalkan pembayaran.'],
        ['label' => 'Paid', 'text' => 'Finance upload bukti bayar dan menandai transaksi sudah dibayar.'],
    ];
@endphp

<style>
    .manual-guide-page {
        --guide-border: rgba(33, 37, 41, .08);
        --guide-shadow: 0 12px 30px rgba(27, 36, 54, .07);
        color: #1f2937;
        padding-bottom: 12px;
    }

    .manual-guide-page .page-kicker {
        font-size: .76rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .manual-guide-page .guide-card,
    .manual-guide-page .guide-panel {
        border: 1px solid var(--guide-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: var(--guide-shadow);
    }

    .manual-guide-page .role-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }

    .manual-guide-page .role-icon iconify-icon {
        width: 24px;
        height: 24px;
        line-height: 1;
    }

    .manual-guide-page .guide-list {
        display: grid;
        gap: 10px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .manual-guide-page .guide-list li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: #53677d;
        line-height: 1.45;
    }

    .manual-guide-page .guide-list iconify-icon {
        width: 18px;
        height: 18px;
        flex: 0 0 auto;
        margin-top: 2px;
        line-height: 1;
    }

    .manual-guide-page .workflow-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(130px, 1fr));
        gap: 12px;
    }

    .manual-guide-page .workflow-step {
        border: 1px solid var(--guide-border);
        border-radius: 12px;
        padding: 14px;
        background: #f8fafc;
        min-height: 126px;
    }

    .manual-guide-page .workflow-number {
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(var(--bs-primary-rgb), .1);
        color: var(--bs-primary);
        font-weight: 700;
        font-size: .8rem;
    }

    @media (max-width: 1199.98px) {
        .manual-guide-page .workflow-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .manual-guide-page .workflow-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="manual-guide-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="page-kicker text-primary mb-1">Manual Guide</div>
            <h3 class="mb-1 fw-bold">Panduan Penggunaan SIGNAL</h3>
            <p class="text-muted mb-0">Ringkasan alur kerja dan hak akses berdasarkan role pengguna.</p>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('invoice-verification.dashboard') }}" class="text-muted">Invoice Verification</a></li>
                    <li class="breadcrumb-item active text-muted" aria-current="page">Panduan</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('invoice-verification.transactions.index') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
            <iconify-icon icon="solar:bill-list-outline" class="fs-18"></iconify-icon>
            Buka Transaksi
        </a>
    </div>

    <div class="guide-panel p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h5 class="mb-1">Alur Status Transaksi</h5>
                <p class="text-muted mb-0">Gunakan urutan ini untuk membaca posisi pekerjaan di dashboard dan daftar transaksi.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2">End-to-end Flow</span>
        </div>
        <div class="workflow-grid">
            @foreach ($workflow as $item)
                <div class="workflow-step">
                    <div class="workflow-number mb-3">{{ $loop->iteration }}</div>
                    <div class="fw-semibold mb-1">{{ $item['label'] }}</div>
                    <div class="text-muted small">{{ $item['text'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="row g-3">
        @foreach ($roleGuides as $guide)
            <div class="col-xl-6">
                <div class="guide-card h-100 p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="role-icon bg-primary-subtle text-primary">
                            <iconify-icon icon="{{ $guide['icon'] }}" class="fs-24"></iconify-icon>
                        </span>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <h5 class="mb-0">{{ $guide['role'] }}</h5>
                                <span class="badge bg-light text-dark border">{{ $guide['badge'] }}</span>
                            </div>
                            <p class="text-muted mb-0">{{ $guide['summary'] }}</p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="fw-semibold mb-2">Bisa melakukan</div>
                            <ul class="guide-list">
                                @foreach ($guide['can'] as $item)
                                    <li>
                                        <iconify-icon icon="solar:check-circle-outline" class="text-success fs-18"></iconify-icon>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-lg-6">
                            <div class="fw-semibold mb-2">Cara menggunakan</div>
                            <ul class="guide-list">
                                @foreach ($guide['steps'] as $item)
                                    <li>
                                        <iconify-icon icon="solar:round-alt-arrow-right-outline" class="text-primary fs-18"></iconify-icon>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
