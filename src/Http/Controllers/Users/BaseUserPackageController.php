<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use Illuminate\Database\Eloquent\Builder;
use IlBronza\CRUD\CRUD;

class BaseUserPackageController extends CRUD
{
    public function setModelClass()
    {
        $this->modelClass = config("accountmanager.models.user.class");
    }

    public function getBaseUserQuery() : Builder
    {
        return $this->getModelClass()::withoutGlobalScope(ActiveScope::class);
    }

    public function getUserModel(int|string $user)
    {
        return $this->getBaseUserQuery()->find($user);
    }

}
