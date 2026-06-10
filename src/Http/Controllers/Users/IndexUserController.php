<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use IlBronza\CRUD\Scopes\ActiveScope;
use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;
use Auth;

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
        $query = $this->getModelClass()::query();

        if(! Auth::user()->isSuperadmin())
            $query->where('active', true);

        $users =  $query->with(['roles', 'permissions', 'latestAccessLog'])
            ->get();

        return $this->withHeartbeatOnline($users);
    }

    public function getIndexFieldsArray()
    {
        return config('accountmanager.models.user.fieldsGroupsFiles.index')::getTracedFieldsGroup();
    }
}
