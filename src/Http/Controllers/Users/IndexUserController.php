<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

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
        return $this->getBaseUserQuery()->get();
    }

    public function getIndexFieldsArray()
    {
        return config('accountmanager.models.user.fieldsGroupsFiles.index')::getFieldsGroup();
    }
}
