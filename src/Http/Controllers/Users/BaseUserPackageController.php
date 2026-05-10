<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Http\Controllers\BasePackageTrait;
use IlBronza\CRUD\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Builder;

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


}
