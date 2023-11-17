<?php

namespace IlBronza\AccountManager\Models;

use IlBronza\AccountManager\Models\Traits\PackageAccountModelsTrait;

use IlBronza\Notifications\Traits\ExtendedNotifiable;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
	use PackageAccountModelsTrait;
	use ExtendedNotifiable;

	static $deletingRelationships = [];

	static $packageConfigPrefix = 'accountmanager';
	static $modelConfigPrefix = 'role';

	protected $fillable = ['name', 'guard_name'];

	public function getTranslatedClassname()
	{
		return trans('crudModels.' . $this->getCamelcaseClassBasename());
	}

}
