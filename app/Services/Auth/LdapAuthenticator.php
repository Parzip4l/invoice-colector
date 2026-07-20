<?php

namespace App\Services\Auth;

use RuntimeException;

class LdapAuthenticator
{
    public function attempt(string $email, string $password): bool
    {
        if ($email === '' || $password === '') {
            return false;
        }

        if (! extension_loaded('ldap')) {
            throw new RuntimeException('PHP LDAP extension is not installed.');
        }

        $connection = $this->connect();

        try {
            $userDn = $this->resolveUserDn($connection, $email);

            return @ldap_bind($connection, $userDn, $password) === true;
        } finally {
            ldap_unbind($connection);
        }
    }

    /**
     * @return resource
     */
    private function connect()
    {
        $connection = ldap_connect((string) config('ldap.host'), (int) config('ldap.port'));

        if (! $connection) {
            throw new RuntimeException('Unable to connect to LDAP server.');
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, (int) config('ldap.timeout', 5));
        ldap_set_option($connection, LDAP_OPT_REFERRALS, filter_var(config('ldap.follow_referrals'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0);

        if (filter_var(config('ldap.use_tls'), FILTER_VALIDATE_BOOLEAN) && @ldap_start_tls($connection) !== true) {
            throw new RuntimeException('Unable to start LDAP TLS.');
        }

        return $connection;
    }

    /**
     * @param resource $connection
     */
    private function resolveUserDn($connection, string $email): string
    {
        $serviceUsername = trim((string) config('ldap.username', ''));
        $servicePassword = (string) config('ldap.password', '');
        $baseDn = trim((string) config('ldap.base_dn', ''));

        if ($serviceUsername === '' || $baseDn === '') {
            return $email;
        }

        if (@ldap_bind($connection, $serviceUsername, $servicePassword) !== true) {
            throw new RuntimeException('Unable to bind LDAP service account.');
        }

        $attribute = preg_replace('/[^a-zA-Z0-9_.-]/', '', (string) config('ldap.login_attribute', 'mail')) ?: 'mail';
        $filter = sprintf('(%s=%s)', $attribute, ldap_escape($email, '', LDAP_ESCAPE_FILTER));
        $search = @ldap_search($connection, $baseDn, $filter, ['dn'], 0, 1);

        if (! $search) {
            return $email;
        }

        $entries = ldap_get_entries($connection, $search);

        if (($entries['count'] ?? 0) < 1 || empty($entries[0]['dn'])) {
            return $email;
        }

        return (string) $entries[0]['dn'];
    }
}
