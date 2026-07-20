<?php

return [
    'enabled' => env('LDAP_ENABLED', false),

    'domain' => env('LDAP_DOMAIN', ''),
    'cache' => env('LDAP_CACHE', false),
    'logging' => env('LDAP_LOGGING', false),
    'connection' => env('LDAP_CONNECTION', 'default'),

    'local_fallback' => env('LDAP_LOCAL_FALLBACK', env('APP_ENV') === 'local'),

    'login_attribute' => env('LDAP_LOGIN_ATTRIBUTE', 'mail'),

    'host' => env('LDAP_HOST', 'ldap://localhost'),
    'port' => (int) env('LDAP_PORT', 389),
    'base_dn' => env('LDAP_BASE_DN', ''),
    'group_dn' => env('LDAP_GROUP_DN', ''),
    'username' => env('LDAP_USERNAME', ''),
    'password' => env('LDAP_PASSWORD', ''),
    'use_ssl' => env('LDAP_SSL', false),
    'use_tls' => env('LDAP_TLS', env('LDAP_USE_TLS', false)),
    'use_sasl' => env('LDAP_SASL', false),
    'timeout' => (int) env('LDAP_TIMEOUT', 5),
    'follow_referrals' => env('LDAP_FOLLOW_REFERRALS', false),
];
