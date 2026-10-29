<?php

namespace IlBronza\AccountManager\Auth\Ldap;

use DomainException;
use IlBronza\AccountManager\Models\User;
use Illuminate\Database\Eloquent\Model;

class LdapAccounts
{
    public const TABLE = 'accountmanager_ldap_identities';

    public function resolve(DirectoryIdentity $identity): ?Model
    {
        $model = User::getProjectClassName();
        $id = (new $model)->getConnection()->table(self::TABLE)
            ->where('connection', $identity->connection)->where('guid', $identity->guid)->value('user_id');

        // No implicit linking by email, provisioning or bypass of model scopes.
        $user = $id === null ? null : $model::find($id);
        if (! $user || ! $user->active || (method_exists($user, 'trashed') && $user->trashed())) {
            return null;
        }

        if (config('accountmanager_ldap.sync_name') && filled($identity->name)) {
            $user->name = $identity->name;
            $user->save();
        }

        return $user;
    }

    public function link(string $userId, DirectoryIdentity $identity): void
    {
        $model = User::getProjectClassName();
        $connection = (new $model)->getConnection();
        $connection->transaction(function () use ($model, $connection, $userId, $identity) {
            $user = $model::whereKey($userId)->lockForUpdate()->first();
            if (! $user || ! $user->active) {
                throw new DomainException('Local user not found or inactive.');
            }
            $links = $connection->table(self::TABLE)->where('connection', $identity->connection)
                ->where(function ($query) use ($identity, $user) {
                    $query->where('guid', $identity->guid)->orWhere('user_id', (string) $user->getKey());
                })->lockForUpdate()->get();

            if ($links->contains(fn ($link) => $link->guid !== $identity->guid || (string) $link->user_id !== (string) $user->getKey())) {
                throw new DomainException('An existing LDAP association conflicts. Unlink it explicitly first.');
            }
            if ($links->isEmpty()) {
                $connection->table(self::TABLE)->insert([
                    'connection' => $identity->connection, 'guid' => $identity->guid,
                    'user_id' => (string) $user->getKey(), 'created_at' => now(),
                ]);
            }
        });
    }

    public function unlink(string $userId, string $connection): int
    {
        $model = User::getProjectClassName();

        return (new $model)->getConnection()->table(self::TABLE)
            ->where('connection', $connection)->where('user_id', $userId)->delete();
    }
}
