<?php

return [
    'enabled' => env('LDAP_ENABLED', false),

    'local_fallback' => env('LDAP_LOCAL_FALLBACK', env('APP_ENV') === 'local'),

    'login_attribute' => env('LDAP_LOGIN_ATTRIBUTE', 'mail'),

    'host' => env('LDAP_HOST', 'ldap://localhost'),
    'port' => (int) env('LDAP_PORT', 389),
    'base_dn' => env('LDAP_BASE_DN', ''),
    'username' => env('LDAP_USERNAME', ''),
    'password' => env('LDAP_PASSWORD', ''),
    'use_tls' => env('LDAP_USE_TLS', false),
    'timeout' => (int) env('LDAP_TIMEOUT', 5),
    'follow_referrals' => env('LDAP_FOLLOW_REFERRALS', false),
];
