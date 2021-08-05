<?php

namespace IlBronza\AccountManager;

use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\User;

class AccountManager
{
	static function getSuperAdministrator()
	{
		mori(Role::with('users')->get());
		return User::role('superadmin')->get();
	}
}