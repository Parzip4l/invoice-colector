<?php

return [
    'storage' => [
        'documents_disk' => env('INVOICE_VERIFICATION_DOCUMENTS_DISK', 'public'),
        'compiled_disk' => env('INVOICE_VERIFICATION_COMPILED_DISK', 'public'),
        'archive_disk' => env('INVOICE_VERIFICATION_ARCHIVE_DISK', 'public'),
        'max_upload_kb' => (int) env('INVOICE_VERIFICATION_MAX_UPLOAD_KB', 10240),
        'allowed_mimes' => [
            'pdf',
            'jpg',
            'jpeg',
            'png',
            'doc',
            'docx',
            'xls',
            'xlsx',
        ],
    ],
    'roles' => [
        'ADMIN_DIVISI' => 'Admin Divisi',
        'KEPALA_DEPARTEMEN' => 'Kepala Departemen',
        'KEPALA_DIVISI' => 'Kepala Divisi',
        'USER_DIVISI' => 'User Divisi',
        'VENDOR' => 'Vendor',
        'AKUNTANSI' => 'Akuntansi',
        'FINANCE' => 'Finance',
    ],
    'document_compile_order' => [
        'PPA' => [
            'PPA_LEMBAR_AWAL',
            'PPA_LEMBAR_VERIFIKASI',
            'PPA_INVOICE',
            'PPA_KWITANSI',
            'PPA_FAKTUR_PAJAK',
            'PPA_BAPP',
            'PPA_BAST',
            'PPA_MEMO_PERMOHONAN',
            'PPA_PERJANJIAN',
            'PPA_LAMPIRAN_PEKERJAAN',
            'PPA_LAPORAN_PEKERJAAN',
        ],
        'SPU' => [
            'SPU_COMBINED_INTERNAL',
            'SPU_COMBINED_VENDOR',
        ],
        'SPUK' => [
            'SPUK_COMBINED_INTERNAL',
            'SPUK_COMBINED_VENDOR',
        ],
        'KAS_KECIL' => [
            'KAS_KECIL_COMBINED_INTERNAL',
            'KAS_KECIL_COMBINED_VENDOR',
        ],
    ],
];
