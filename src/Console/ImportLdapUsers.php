<?php

namespace IlBronza\AccountManager\Console;

use IlBronza\AccountManager\Auth\Ldap\DirectoryReader;
use IlBronza\AccountManager\Auth\Ldap\LdapDirectoryUsers;
use Illuminate\Console\Command;
use Throwable;

class ImportLdapUsers extends Command
{
    protected $signature = 'accountmanager:ldap-import {--dry-run : Read the full directory without writing the inventory}';

    protected $description = 'Import all AD user identities for manual association without creating local users or assigning roles';

    public function handle(DirectoryReader $directory, LdapDirectoryUsers $inventory): int
    {
        try {
            $stats = $inventory->import($directory, (bool) $this->option('dry-run'));
            $this->table(array_keys($stats), [array_values($stats)]);
            $this->info($this->option('dry-run') ? 'Directory preview complete. No changes saved.'
                : 'Directory imported. Local users, roles, associations and login settings are unchanged.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('LDAP import unavailable ('.get_class($exception).'). Check directory configuration and optional LDAP migrations.');

            return self::FAILURE;
        }
    }
}
