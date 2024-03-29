<?php

namespace IlBronza\AccountManager\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use IlBronza\AccountManager\Models\User;
use IlBronza\Ukn\Ukn;

class RestoreAccountController extends Controller
{
	public function restore($user)
	{
		$user = User::getProjectClassName()::withTrashed()->find($user);
		$user->restore();

		Ukn::s(trans('auth.userRestored', ['user' => $user->name]));

		return back();
	}
}