<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use IlBronza\CRUD\Traits\CRUDCreateStoreTrait;

class CreateUserController extends BaseUserPackageController
{
    use CRUDCreateStoreTrait;
    use \IlBronza\AccountManager\Http\Traits\ValidatesUserAccessChanges;

    public function store(\Illuminate\Http\Request $request, string $model = null)
    {
        $this->validateUserChanges($request);
        return (new ($this->getModelClass()))->getConnection()->transaction(fn () => $this->_store($request));
    }

    public $allowedMethods = ['create', 'store'];

    public function getGenericParametersFile() : ? string
    {
        return config('accountmanager.models.user.parametersFiles.create');
    }

    public function getStoreModelAction()
    {
        return IbRouter::route(app('accountmanager'), 'users.store');
    }

    public function getAfterStoredRedirectUrl()
    {
        return $this->getModel()->getEditUserdataUrl();
    }
}
