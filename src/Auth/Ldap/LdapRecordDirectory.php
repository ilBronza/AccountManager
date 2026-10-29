<?php

namespace IlBronza\AccountManager\Auth\Ldap;

use LdapRecord\Container;
use LdapRecord\Models\ActiveDirectory\Group;
use LdapRecord\Models\ActiveDirectory\User;
use RuntimeException;

class LdapRecordDirectory implements DirectoryAuthenticator, DirectoryReader
{
    public function users(): iterable
    {
        $this->validateConfiguration();
        $allowed = null;
        if ($groupDn = config('accountmanager_ldap.required_group_dn')) {
            $allowed = [];
            // The relationship includes nested and primary-group members and paginates results.
            $group = Group::on($this->connectionName())->findOrFail($groupDn);
            foreach ($group->members()->recursive()->get(['objectguid', 'objectclass', 'objectsid', 'member', 'primarygroupid']) as $member) {
                $allowed[strtolower((string) $member->getConvertedGuid())] = true;
            }
        }
        $users = User::on($this->connectionName())->where('objectcategory', '=', 'person')
            ->select(['objectguid', 'cn', 'displayname', 'samaccountname', 'userprincipalname', 'mail', 'useraccountcontrol'])
            ->paginate(500, true);
        foreach ($users as $user) {
            $identity = $this->identity($user);
            yield new DirectoryUser(
                $identity,
                $user->getFirstAttribute('samaccountname'),
                $user->getFirstAttribute('userprincipalname'),
                $user->getFirstAttribute('mail'),
                $user->getDn(),
                ((int) $user->getFirstAttribute('useraccountcontrol') & 2) === 0,
                $allowed === null || isset($allowed[$identity->guid]),
            );
        }
    }

    public function authenticate(string $username, string $password): ?DirectoryIdentity
    {
        // Never permit anonymous/unauthenticated binds with an empty password.
        if (trim($username) === '' || $password === '') {
            return null;
        }

        $user = $this->lookup($username);
        if (! $user) {
            return null;
        }

        $connection = Container::getConnection($this->connectionName());
        // LdapRecord rebinds the service account after the credential check.
        if (! $connection->auth()->attempt($user->getDn(), $password)) {
            return null;
        }

        return $this->identity($user);
    }

    public function find(string $username): ?DirectoryIdentity
    {
        $user = $this->lookup($username);

        return $user ? $this->identity($user) : null;
    }

    protected function lookup(string $username): ?User
    {
        $this->validateConfiguration();
        if (trim($username) === '') {
            return null;
        }

        // The query builder escapes input. Multiple matches (e.g. duplicate mail)
        // are rejected instead of binding whichever account the server returns first.
        $users = User::on($this->connectionName())
            ->where(config('accountmanager_ldap.login_attribute'), '=', trim($username))->get();

        if ($users->count() !== 1) {
            return null;
        }

        $user = $users->first();
        if (((int) $user->getFirstAttribute('useraccountcontrol') & 2) !== 0) {
            return null;
        }

        $group = config('accountmanager_ldap.required_group_dn');
        if ($group && ! $user->groups()->recursive()->exists($group)) {
            return null;
        }

        return $user;
    }

    protected function identity(User $user): DirectoryIdentity
    {
        return new DirectoryIdentity(
            $this->connectionName(),
            (string) $user->getConvertedGuid(),
            $user->getFirstAttribute('displayname') ?: $user->getFirstAttribute('cn'),
        );
    }

    protected function connectionName(): string
    {
        return (string) config('accountmanager_ldap.connection');
    }

    protected function validateConfiguration(): void
    {
        if (! class_exists(Container::class) || ! extension_loaded('ldap')) {
            throw new RuntimeException('Install directorytree/ldaprecord-laravel:^3.4 and ext-ldap.');
        }

        $connection = Container::getConnection($this->connectionName());
        $config = $connection->getConfiguration();
        // Honor the selected transport, including explicitly configured plain LDAP.
        // SSL and StartTLS are alternative transports and cannot be combined.
        if ($config->get('use_ssl') && $config->get('use_tls')) {
            throw new RuntimeException('LDAPS and StartTLS cannot both be enabled.');
        }
        foreach (['hosts', 'base_dn', 'username', 'password'] as $key) {
            if (! $config->get($key)) {
                throw new RuntimeException('Incomplete LDAP service-account configuration.');
            }
        }
        $options = $config->get('options');
        if (! in_array($options[LDAP_OPT_X_TLS_REQUIRE_CERT] ?? LDAP_OPT_X_TLS_DEMAND,
            [LDAP_OPT_X_TLS_DEMAND, LDAP_OPT_X_TLS_HARD], true)) {
            throw new RuntimeException('LDAP certificate verification must be enabled.');
        }
        // Override any permissive system-wide LDAPTLS_REQCERT setting as well.
        $config->set('options', array_replace($options, [LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_DEMAND]));
        if (! in_array(config('accountmanager_ldap.login_attribute'), ['userprincipalname', 'samaccountname', 'mail'], true)) {
            throw new RuntimeException('Unsupported LDAP login attribute.');
        }
    }
}
