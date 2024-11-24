<?php

namespace IlBronza\AccountManager\Models\Traits;

use Spatie\Permission\Traits\HasRoles;

trait UserPermissionsTrait
{
	use HasRoles;

	public function isSuperAdmin()
	{
		return $this->hasRole('superadmin');
	}

	public function isAdministrator()
	{
		return $this->hasRole('administrator');
	}
}