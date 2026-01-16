<?php

namespace IlBronza\AccountManager\Models\Traits;

use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDModelTranslationsTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;
use IlBronza\CRUD\Traits\Model\PackagedModelsTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

trait PackageAccountModelsTrait
{
	use CRUDModelTrait;

	use PackagedModelsTrait {
		PackagedModelsTrait::getRouteBaseNamePrefix insteadof CRUDModelTrait;
		PackagedModelsTrait::getTranslatedClassname insteadof CRUDModelTranslationsTrait;
		PackagedModelsTrait::getPluralTranslatedClassname insteadof CRUDModelTranslationsTrait;
	}

	use CRUDModelTranslationsTrait;
	use CRUDRelationshipModelTrait;
	use SoftDeletes;
}