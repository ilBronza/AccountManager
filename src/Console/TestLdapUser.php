<?php

namespace IlBronza\AccountManager\Console;

use IlBronza\AccountManager\Auth\Ldap\DirectoryAuthenticator;
use Illuminate\Console\Command;
use Throwable;

class TestLdapUser extends Command
{
    protected $signature = 'accountmanager:ldap-test {username? : Exact AD login name} {--authenticate : Prompt for the AD user password and verify it}';

    protected $description = 'Check an AD user and optional password without changing local users or opening a session';

    public function handle(DirectoryAuthenticator $directory): int
    {
        $username = trim((string) ($this->argument('username') ?? $this->ask('AD login')));
        if ($username === '') {
            $this->error('Provide an AD login name.');

            return self::INVALID;
        }

        $connection = (string) config('accountmanager_ldap.connection');
        $transport = config("ldap.connections.{$connection}.use_ssl") ? 'LDAPS'
            : (config("ldap.connections.{$connection}.use_tls") ? 'StartTLS' : 'LDAP');
        $this->line('Connection: '.$connection.'; transport: '.$transport.'; login attribute: '.config('accountmanager_ldap.login_attribute').'.');

        try {
            $identity = $directory->find($username);
            if (! $identity) {
                $this->error('AD user not found, ambiguous, disabled or outside the required group.');

                return self::FAILURE;
            }

            if ($this->option('authenticate')) {
                $password = $this->secret('AD password (input hidden)', false);
                if (! is_string($password) || $password === '') {
                    $this->error('A non-empty AD password is required.');

                    return self::INVALID;
                }
                try {
                    $identity = $directory->authenticate($username, $password);
                } finally {
                    unset($password);
                }
                if (! $identity) {
                    $this->error('AD credentials rejected.');

                    return self::FAILURE;
                }
                $this->info('AD password verified.');
            }

            $this->table(['Connection', 'AD login', 'Name'], [[$identity->connection, $username, $identity->name]]);
            $this->info('AD user verified. No local users, associations or sessions changed.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            // Exception messages and traces may contain directory or credential details.
            $this->error('LDAP verification unavailable ('.get_class($exception).'). Check connection and configuration.');

            return self::FAILURE;
        }
    }
}
