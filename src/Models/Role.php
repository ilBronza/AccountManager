<?php

namespace IlBronza\AccountManager\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;

class Role extends SpatieRole
{
	use CRUDModelTrait;
	use CRUDRelationshipModelTrait;

	protected $fillable = ['name', 'guard_name'];
}
