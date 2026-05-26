<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Http\Controllers\BasePackageTrait;
use IlBronza\CRUD\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BaseUserPackageController extends CRUD
{
    use BasePackageTrait;

    static $packageConfigPrefix = 'accountmanager';

    public function setModelClass()
    {
        $this->modelClass = config("accountmanager.models.user.class");
    }

    public function getBaseUserQuery() : Builder
    {
        return $this->getModelClass()::query();
    }

    public function getUserModel(int|string $user)
    {
        return $this->getBaseUserQuery()->find($user);
    }

    public function getFindModelQuery(string $key, array $relations = []) : Builder
    {
        $query = $this->getBaseUserQuery();

        foreach($relations as $relation)
            $query->with($relation);

        return $query;
    }

    /**
     * Imposta l’attributo heartbeat_online in base alle chiavi cache heartbeat-{id} (una sola lettura batch).
     *
     * @param  Collection<int, \Illuminate\Database\Eloquent\Model>  $users
     * @return Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    protected function withHeartbeatOnline(Collection $users): Collection
    {
        if (! config('accountmanager.heartbeat.enabled', true)) {
            foreach ($users as $user) {
                $user->setAttribute('heartbeat_online', false);
            }

            return $users;
        }

        $keys = $users->map(fn ($user) => 'heartbeat-' . $user->getKey())->values()->all();
        $presence = $keys === [] ? [] : Cache::many($keys);

        foreach ($users as $user) {
            $key = 'heartbeat-' . $user->getKey();
            $user->setAttribute('heartbeat_online', ! empty($presence[$key]));
        }

        return $users;
    }
}
