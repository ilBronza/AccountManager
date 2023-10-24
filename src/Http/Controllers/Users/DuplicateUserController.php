<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use IlBronza\Ukn\Facades\Ukn;

class DuplicateUserController extends BaseUserPackageController
{
    public $allowedMethods = ['duplicate'];

	public function duplicate($user)
	{
		$user = $this->getUserModel($user);

		$clone = $user->replicate();

		$clone->name = 'copy_' . $user->name;
		$clone->email = 'copy_' . $user->email;
		$clone->push();

		foreach($user->roles as $role)
		{
			$clone->assignRole($role);
		}

		foreach($user->permissions as $permission)
		{
			$clone->givePermissionTo($permission);
		}

		Ukn::s(trans('acountmanager.userCloned', ['user' => $user->name]));

		return back();
	}
}