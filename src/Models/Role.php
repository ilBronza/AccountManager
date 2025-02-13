<?php

namespace IlBronza\AccountManager\Models;

use IlBronza\AccountManager\Models\Traits\PackageAccountModelsTrait;

use IlBronza\CRUD\Traits\Model\CRUDCacheTrait;
use IlBronza\Notifications\Traits\ExtendedNotifiable;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
	use CRUDCacheTrait;
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

	static function getAreaManagerRole()
	{
		return static::where('name', 'areaManager')->firstOrFail();
	}

	static function getCompanyAdministrativeRole() : static
	{
		return cache()->remember(
			static::staticCacheKey('administrativeRole'),
			3600 * 24,
			function()
			{
				if($role = static::where('name', 'companyAdministrative')->first())
					return $role;

				$role = static::make();

				$role->name = 'companyAdministrative';
				$role->save();

				return $role;
			}
		);
	}
}
