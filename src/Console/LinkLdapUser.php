<?php

namespace IlBronza\AccountManager\Console;

use IlBronza\AccountManager\Auth\Ldap\DirectoryAuthenticator;
use IlBronza\AccountManager\Auth\Ldap\LdapAccounts;
use Illuminate\Console\Command;
use DomainException;
use Throwable;

class LinkLdapUser extends Command
{
    protected $signature = 'accountmanager:ldap-link {user : Existing local user ID} {username? : Exact AD login name} {--unlink : Remove this user association for the configured directory}';

    protected $description = 'Explicitly link an existing local user to an Active Directory GUID';

    public function handle(LdapAccounts $accounts): int
    {
        try {
            if ($this->option('unlink')) {
                $count = $accounts->unlink((string) $this->argument('user'), (string) config('accountmanager_ldap.connection'));
                $this->info("Removed LDAP associations: {$count}.");

                return self::SUCCESS;
            }
            if (! $username = $this->argument('username')) {
                $this->error('Provide an AD username or --unlink.');

                return self::FAILURE;
            }
            $identity = app(DirectoryAuthenticator::class)->find($username);
            if (! $identity) {
                $this->error('AD account not found, ambiguous, disabled or outside the required group.');

                return self::FAILURE;
            }
            $accounts->link((string) $this->argument('user'), $identity);
            $this->info('LDAP association saved. Local credentials and permissions are unchanged.');

            return self::SUCCESS;
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());
        } catch (Throwable $exception) {
            $this->error('LDAP linking unavailable. Check dependencies, connection and the optional identity migration.');
        }

        return self::FAILURE;
    }
}
