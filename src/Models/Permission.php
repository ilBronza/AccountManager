<?php

namespace IlBronza\AccountManager\Models;

use IlBronza\AccountManager\Models\Traits\PackageAccountModelsTrait;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
	use PackageAccountModelsTrait;

	static $packageConfigPrefix = 'accountmanager';
	static $modelConfigPrefix = 'permission';

	protected $fillable = ['name', 'guard_name'];

	public function getTranslatedClassname()
	{
		return trans('crudModels.' . $this->getCamelcaseClassBasename());
	}
}
