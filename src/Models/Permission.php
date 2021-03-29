<?php

namespace IlBronza\AccountManager\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use ilBronza\CRUD\Traits\Model\CRUDModelTrait;
use ilBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;

class Permission extends SpatiePermission
{
	use CRUDModelTrait;
	use CRUDRelationshipModelTrait;

	protected $fillable = ['name', 'guard_name'];
}
