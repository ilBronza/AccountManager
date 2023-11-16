<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use IlBronza\CRUD\Traits\CRUDCreateStoreTrait;

class CreateUserController extends BaseUserPackageController
{
    use CRUDCreateStoreTrait;

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
