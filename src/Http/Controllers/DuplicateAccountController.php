<?php

namespace IlBronza\AccountManager\Http\Controllers;

use App\Http\Controllers\Controller;
use IlBronza\AccountManager\Models\User;
use IlBronza\Ukn\Facades\Ukn;

class DuplicateAccountController extends Controller
{
	public function duplicate(User $user)
	{
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