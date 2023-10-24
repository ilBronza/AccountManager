<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use Illuminate\Http\Request;

class EditUserController extends BaseUserPackageController
{
    use CRUDEditUpdateTrait;

    public $allowedMethods = [
        'edit',
        'update'
    ];

    public function getGenericParametersFile() : ? string
    {
        return config('accountmanager.models.user.parametersFiles.edit');
    }

    public function edit($user)
    {
        $user = $this->getUserModel($user);

        return $this->_edit($user);
    }

    public function update(Request $request, $user)
    {
        $user = $this->getUserModel($user);

        return $this->_update($request, $user);
    }
}
