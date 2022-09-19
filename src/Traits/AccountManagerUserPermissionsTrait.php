<?php

namespace IlBronza\AccountManager\Traits;

use Spatie\Permission\Traits\HasRoles;

trait AccountManagerUserPermissionsTrait
{
	use HasRoles;

	public function isSuperAdmin()
	{
		return $this->hasRole('superadmin');
	}
}