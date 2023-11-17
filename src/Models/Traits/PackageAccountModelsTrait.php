<?php

namespace IlBronza\AccountManager\Models\Traits;

use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;
use IlBronza\CRUD\Traits\Model\PackagedModelsTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

trait PackageAccountModelsTrait
{
	use CRUDModelTrait;
	use PackagedModelsTrait {
		PackagedModelsTrait::getRouteBaseNamePrefix insteadof CRUDModelTrait;
		PackagedModelsTrait::getPluralTranslatedClassname insteadof CRUDModelTrait;
		PackagedModelsTrait::getTranslatedClassname insteadof CRUDModelTrait;
	}

	use CRUDRelationshipModelTrait;
	use SoftDeletes;
}