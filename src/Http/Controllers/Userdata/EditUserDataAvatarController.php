<?php

namespace IlBronza\AccountManager\Http\Controllers\Userdata;

use IlBronza\AccountManager\Models\User;
use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use Illuminate\Http\Request;

use function app;

class EditUserDataAvatarController extends EditUserDataController
{
	public $allowedMethods = ['edit', 'update', 'userEdit', 'userUpdate'];

    public function getGenericParametersFile() : ? string
    {
        return config('accountmanager.models.userdata.parametersFiles.editAvatar');
    }

	public function getUpdateModelAction()
	{
		if($this->user)
			return IbRouter::route(app('accountmanager'), 'accountmanager.user.updateAvatar', ['user' => $this->user]);

		return IbRouter::route(app('accountmanager'), 'accountmanager.updateAvatar');
	}

	public function userEdit(string $user)
	{
		$this->user = User::gpc()::find($user);

		return $this->edit();
	}

	public function userUpdate(Request $request, string $user)
	{
		$this->user = User::gpc()::find($user);

		return $this->update($request);
	}

}