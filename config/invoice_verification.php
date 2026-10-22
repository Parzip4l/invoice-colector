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
    'operations' => [
        'allow_production_reset' => filter_var(env('INVOICE_VERIFICATION_ALLOW_PRODUCTION_RESET', false), FILTER_VALIDATE_BOOLEAN),
        'production_reset_token' => env('INVOICE_VERIFICATION_PRODUCTION_RESET_TOKEN', ''),
    ],
    'eproc' => [
        'driver' => env('EPROC_DRIVER', 'local'),
        'base_url' => env('EPROC_BASE_URL', 'https://eproc-lrt-api.cendana2000.biz'),
        'vendor_endpoint' => env('EPROC_VENDOR_ENDPOINT', 'detail_purchase_order_vendor'),
        'token' => env('EPROC_TOKEN'),
        'timeout' => (int) env('EPROC_TIMEOUT', 30),
    ],
    'roles' => [
        'SUPERADMIN' => 'Super Admin',
        'ADMIN_DIVISI' => 'Admin Divisi',
        'KEPALA_DEPARTEMEN' => 'Kepala Departemen',
        'KEPALA_DIVISI' => 'Kepala Divisi',
        'USER_DIVISI' => 'User Divisi',
        'VENDOR' => 'Vendor',
        'AKUNTANSI' => 'Akuntansi',
        'FINANCE' => 'Finance',
    ],
    'approval_resolver' => [
        'base_url' => env('INVOICE_COLLECTOR_APPROVAL_RESOLVER_BASE_URL', env('LRTJ_SPACE_BASE_URL', 'https://portal.lrtjakarta.co.id')),
        'endpoint' => env('INVOICE_COLLECTOR_APPROVAL_RESOLVER_ENDPOINT', '/api/v1/approval/resolve'),
        'signature_endpoint' => env('INVOICE_COLLECTOR_PORTAL_SIGNATURE_ENDPOINT', '/api/v1/signatures'),
        'notification_endpoint' => env('INVOICE_COLLECTOR_PORTAL_NOTIFICATION_ENDPOINT', '/api/v1/invoice-collector/notifications'),
        'client_code' => env('INVOICE_COLLECTOR_PORTAL_CLIENT_CODE', 'invoice_collector'),
        'shared_secret' => env('INVOICE_COLLECTOR_PORTAL_SHARED_SECRET', env('LRTJ_SPACE_SATSET_SHARED_SECRET', '')),
        'timeout' => (int) env('INVOICE_COLLECTOR_APPROVAL_RESOLVER_TIMEOUT', 15),
        'verify_ssl' => (bool) env('INVOICE_COLLECTOR_APPROVAL_RESOLVER_VERIFY_SSL', true),
    ],
    'portal_api' => [
        'shared_secret' => env('INVOICE_COLLECTOR_PORTAL_SHARED_SECRET', env('LRTJ_SPACE_SATSET_SHARED_SECRET', '')),
        'signature_ttl' => (int) env('INVOICE_COLLECTOR_PORTAL_SIGNATURE_TTL', 300),
    ],
    'mobile_sso' => [
        'shared_secret' => env('INVOICE_COLLECTOR_MOBILE_SSO_SHARED_SECRET', env('INVOICE_COLLECTOR_PORTAL_SHARED_SECRET', '')),
        'signature_tolerance_seconds' => (int) env('INVOICE_COLLECTOR_MOBILE_SSO_SIGNATURE_TOLERANCE_SECONDS', 300),
        'token_ttl_minutes' => (int) env('INVOICE_COLLECTOR_MOBILE_TOKEN_TTL_MINUTES', 129600),
    ],
    'budget_api' => [
        'base_url' => env('BUDGET_API_BASE_URL', 'https://versus.lrtjakarta.co.id'),
        'timeout' => (int) env('BUDGET_API_TIMEOUT', 10),
        'verify_ssl' => (bool) env('BUDGET_API_VERIFY_SSL', true),
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
