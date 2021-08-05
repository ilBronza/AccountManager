<?php

namespace IlBronza\AccountManager\Models;

use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;
use IlBronza\Notifications\Traits\ExtendedNotifiable;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
	use CRUDModelTrait;
	use CRUDRelationshipModelTrait;

	use ExtendedNotifiable;

	protected $fillable = ['name', 'guard_name'];
}
