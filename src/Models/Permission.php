<?php

namespace IlBronza\AccountManager\Models;

use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;
use IlBronza\CRUD\Traits\Model\PackagedModelsTrait;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
	use CRUDModelTrait;
	use CRUDRelationshipModelTrait;

	use PackagedModelsTrait {
		PackagedModelsTrait::getRouteBaseNamePrefix insteadof CRUDModelTrait;
	}

	static $packageConfigPrefix = 'accountmanager';
	static $modelConfigPrefix = 'permission';

	protected $fillable = ['name', 'guard_name'];

	public function getTranslatedClassname()
	{
		return trans('crudModels.' . $this->getCamelcaseClassBasename());
	}
}
