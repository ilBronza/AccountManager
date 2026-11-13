<?php

namespace IlBronza\AccountManager\Models;

use IlBronza\AccountManager\Models\Traits\PackageAccountModelsTrait;

use IlBronza\CRUD\Traits\Model\CRUDCacheTrait;
use IlBronza\AccountManager\Models\Traits\RoleNotifiableTrait;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
	use CRUDCacheTrait;
	use PackageAccountModelsTrait;
	use RoleNotifiableTrait;

	static $deletingRelationships = [];

	static $packageConfigPrefix = 'accountmanager';
	static $modelConfigPrefix = 'role';

	protected $fillable = ['name', 'guard_name', 'allow_from_remote', 'allowed_ips'];

	protected $casts = [
		'allow_from_remote' => 'boolean'
	];

    public function routes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(config('accountmanager.models.route.class', Route::class), 'accountmanager_route_role', 'role_id', 'route_id');
    }

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
