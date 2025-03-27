<?php

namespace IlBronza\AccountManager\Http\Controllers\Userdata;

use Auth;
use IlBronza\AccountManager\Models\User;
use IlBronza\CRUD\CRUD;

class BaseUserdataPackageController extends CRUD
{
	public ? User $user = null;

    public function setModelClass()
    {
        $this->modelClass = config("accountmanager.models.userdata.class");
    }

	public function getUserdata()
	{
		if($this->user)
			return $this->user->getUserData();

		$userData = Auth::user()->getUserData();

		return Auth::user()->refreshUserdata();
	}
}