<?php

namespace IlBronza\AccountManager\Http\Controllers;

use App\Http\Controllers\Controller;
use IlBronza\AccountManager\Models\User;
use IlBronza\CRUD\Models\Scopes\ActiveScope;
use IlBronza\Ukn\Facades\Ukn;

class DuplicateAccountController extends Controller
{
    public function getUserModel(int|string $user)
    {
        return User::getProjectClassName()::withoutGlobalScope(ActiveScope::class)->find($user);
    }

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