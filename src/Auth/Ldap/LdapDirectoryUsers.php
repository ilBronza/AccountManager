<?php

namespace IlBronza\AccountManager\Auth\Ldap;

use IlBronza\AccountManager\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use RuntimeException;

class LdapDirectoryUsers
{
    public const TABLE = 'accountmanager_ldap_directory_users';

    public function database(): Connection
    {
        $model = User::getProjectClassName();

        return (new $model)->getConnection();
    }

    public function query(): Builder
    {
        return $this->database()->table(self::TABLE)
            ->where('connection', (string) config('accountmanager_ldap.connection'));
    }

    public function ready(): bool
    {
        $schema = $this->database()->getSchemaBuilder();

        return $schema->hasTable(self::TABLE) && $schema->hasTable(LdapAccounts::TABLE);
    }

    public function import(DirectoryReader $directory, bool $dryRun = false): array
    {
        // Read and validate the entire snapshot before changing the inventory.
        // A failed or partial LDAP read must leave the last successful import intact.
        $connection = (string) config('accountmanager_ldap.connection');
        $rows = [];
        $now = now();
        $token = (string) Str::uuid();
        $active = $eligible = 0;
        foreach ($directory->users() as $user) {
            if (! $user instanceof DirectoryUser || $user->identity->connection !== $connection || isset($rows[$user->identity->guid])) {
                throw new RuntimeException('Invalid or duplicate directory identity in import.');
            }
            $active += (int) $user->enabled;
            $eligible += (int) ($user->enabled && $user->inRequiredGroup);
            $rows[$user->identity->guid] = [
                'connection' => $connection, 'guid' => $user->identity->guid,
                'samaccountname' => $user->samAccountName, 'userprincipalname' => $user->userPrincipalName,
                'name' => $user->identity->name, 'email' => $user->email, 'dn' => $user->dn,
                'enabled' => $user->enabled, 'in_required_group' => $user->inRequiredGroup,
                'missing' => false, 'import_token' => $token, 'last_seen_at' => $now,
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        $stats = ['total' => count($rows), 'active' => $active, 'eligible' => $eligible];
        if ($dryRun) {
            return $stats;
        }
        if (! $this->ready()) {
            throw new RuntimeException('Publish and run the optional AccountManager LDAP migrations first.');
        }

        return $this->database()->transaction(function () use ($rows, $stats, $token, $now, $connection) {
            // Serialize refreshes of an existing inventory for the same directory.
            $this->query()->orderBy('id')->lockForUpdate()->get(['id']);
            $updates = ['samaccountname', 'userprincipalname', 'name', 'email', 'dn', 'enabled',
                'in_required_group', 'missing', 'import_token', 'last_seen_at', 'updated_at'];
            foreach (array_chunk(array_values($rows), 250) as $chunk) {
                $this->database()->table(self::TABLE)->upsert($chunk, ['connection', 'guid'], $updates);
            }
            // Preserve both absent identities and all manually created associations.
            $this->query()->where('import_token', '!=', $token)->update(['missing' => true, 'updated_at' => $now]);
            $linked = $this->database()->table(self::TABLE.' as directory')
                ->join(LdapAccounts::TABLE.' as links', function ($join) {
                    $join->on('links.connection', '=', 'directory.connection')->on('links.guid', '=', 'directory.guid');
                })->where('directory.connection', $connection)->where('directory.import_token', $token)->count();

            return $stats + ['linked' => $linked, 'unlinked' => $stats['total'] - $linked,
                'missing' => $this->query()->where('missing', true)->count()];
        }, 3);
    }
}
