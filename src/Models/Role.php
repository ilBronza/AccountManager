<?php

namespace IlBronza\AccountManager\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use ilBronza\CRUD\Traits\Model\CRUDModelTrait;
use ilBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;

class Role extends SpatieRole
{
	use CRUDModelTrait;
	use CRUDRelationshipModelTrait;

	protected $fillable = ['name', 'guard_name'];
}
