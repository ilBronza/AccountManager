<?php

namespace IlBronza\AccountManager\Models;

use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;
use IlBronza\CRUD\Traits\Model\PackagedModelsTrait;
use IlBronza\Notifications\Traits\ExtendedNotifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
	use CRUDModelTrait;
	use CRUDRelationshipModelTrait;
	use SoftDeletes;

	static $deletingRelationships = [];

	use PackagedModelsTrait {
		PackagedModelsTrait::getRouteBaseNamePrefix insteadof CRUDModelTrait;
	}

	use ExtendedNotifiable;

	static $packageConfigPrefix = 'accountmanager';
	static $modelConfigPrefix = 'role';

	protected $fillable = ['name', 'guard_name'];

	public function getTranslatedClassname()
	{
		return trans('crudModels.' . $this->getCamelcaseClassBasename());
	}

}
