<?php

namespace IlBronza\AccountManager\Models;

use IlBronza\AccountManager\Models\Traits\PackageAccountModelsTrait;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public static $deletingRelationships = [];
	use PackageAccountModelsTrait;

	static $packageConfigPrefix = 'accountmanager';
	static $modelConfigPrefix = 'permission';

	protected $fillable = ['name', 'guard_name'];

    public function routes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(config('accountmanager.models.route.class', Route::class), 'accountmanager_route_permission', 'permission_id', 'route_id');
    }

	public function getTranslatedClassname()
	{
		return trans('crudModels.' . $this->getCamelcaseClassBasename());
	}
}
