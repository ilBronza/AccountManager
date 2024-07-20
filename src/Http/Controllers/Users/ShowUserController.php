<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use IlBronza\CRUD\Traits\CRUDRelationshipTrait;
use IlBronza\CRUD\Traits\CRUDShowTrait;

class ShowUserController extends BaseUserPackageController
{
    use CRUDShowTrait;
    use CRUDRelationshipTrait;

    public $allowedMethods = ['show'];

    public function getGenericParametersFile() : ? string
    {
        return config('accountmanager.models.user.parametersFiles.show');
    }

    public function getRelationshipsManagerClass()
    {
        return config("accountmanager.models.user.relationshipsManagerClasses.show");
    }

    public function show(string $user)
    {
        $user = $this->findModel($user);

        return $this->_show($user);
    }
}
