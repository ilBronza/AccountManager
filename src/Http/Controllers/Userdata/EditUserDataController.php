<?php

namespace IlBronza\AccountManager\Http\Controllers\Userdata;

use Illuminate\Http\Request;

use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;

class EditUserDataController extends BaseUserdataPackageController
{
    use CRUDEditUpdateTrait;

    public $allowedMethods = ['edit', 'update'];

    public function getGenericParametersFile() : ? string
    {
        return config('accountmanager.models.userdata.parametersFiles.edit');
    }

	public function edit()
	{
		return $this->_edit(
			$this->getUserData()
		);
	}

	public function getUpdateModelAction()
	{
		return route('accountmanager.updateUserdata');
	}

	public function update(Request $request)
	{
		return $this->_update(
			$request, 
			$this->getUserData()
		);
	}

}