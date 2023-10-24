<?php

namespace IlBronza\AccountManager\Http\Controllers\Userdata;

use Auth;
use IlBronza\CRUD\CRUD;

class BaseUserdataPackageController extends CRUD
{
    public function setModelClass()
    {
        $this->modelClass = config("accountmanager.models.userdata.class");
    }

	public function getUserdata()
	{
		$userData = Auth::user()->getUserData();

		return Auth::user()->refreshUserdata();
	}
}