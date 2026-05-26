<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use IlBronza\CRUD\Scopes\ActiveScope;
use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;

class IndexUserController extends BaseUserPackageController
{
    use CRUDPlainIndexTrait;
    use CRUDIndexTrait;

    public $allowedMethods = ['index'];

    public function setModelClass()
    {
        $this->modelClass = config("accountmanager.models.user.class");
    }

    public function getIndexElements()
    {
        $users = $this->getModelClass()::where('active', true)
            ->with(['roles', 'permissions', 'latestAccessLog'])
            ->get();

        return $this->withHeartbeatOnline($users);
    }

    public function getIndexFieldsArray()
    {
        return config('accountmanager.models.user.fieldsGroupsFiles.index')::getTracedFieldsGroup();
    }
}
