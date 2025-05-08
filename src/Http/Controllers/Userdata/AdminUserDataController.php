<?php

namespace IlBronza\AccountManager\Http\Controllers\Userdata;

use IlBronza\AccountManager\Http\Controllers\Userdata\BaseUserdataPackageController;
use IlBronza\AccountManager\Models\User;
use IlBronza\AccountManager\Models\Userdata;
use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use IlBronza\CRUD\Traits\CRUDRelationshipTrait;
use IlBronza\CRUD\Traits\CRUDShowRelationshipsTrait;
use IlBronza\CRUD\Traits\CRUDShowTrait;
use Illuminate\Http\Request;

class AdminUserDataController extends BaseUserdataPackageController
{
	use CRUDShowTrait;
	use CRUDShowRelationshipsTrait;
	use CRUDRelationshipTrait;
	use CRUDEditUpdateTrait;

    public $allowedMethods = ['show', 'edit', 'update'];

    public function getGenericParametersFile() : ? string
    {
        return config('accountmanager.models.userdata.parametersFiles.edit');
    }

	public function edit($user)
	{
		if(is_string($user))
			$user = User::gpc()::find($user);

		$this->user = $user;

		return $this->_edit(
			Userdata::getProjectClassName()::getByUser($user)
		);
	}

	public function show($user)
	{
		return $this->_show(
			Userdata::getProjectClassName()::getByUser($user)
		);
	}

	public function getUpdateModelAction()
	{
		return $this->getModel()->getUpdateUrl();
//		return app('accountmanager')->route('userdatas.update', ['userdata' => $this->user]);
	}

	public function getAfterUpdatedRedirectUrl()
	{
		return app('accountmanager')->route('userdatas.show', ['userdata' => $this->getModel()->getKey()]);
	}

	public function update(Request $request, $user)
	{
		return $this->_update(
			$request, 
			Userdata::getProjectClassName()::getByUser($user)
		);
	}

}