<?php

namespace IlBronza\AccountManager\Http\Controllers\Userdata;

use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use Illuminate\Http\Request;

class EditUserDataAvatarController extends EditUserDataController
{
    public function getGenericParametersFile() : ? string
    {
        return config('accountmanager.models.userdata.parametersFiles.editAvatar');
    }

	public function getUpdateModelAction()
	{
		return IbRouter::route(app('accountmanager'), 'accountmanager.updateAvatar');
	}
}