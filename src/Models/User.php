<?php

namespace IlBronza\AccountManager\Models;

use App\Models\User as BaseUser;
use IlBronza\AccountManager\Traits\AccountManagerUserPermissionsTrait;
use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;
use IlBronza\Notifications\Traits\ExtendedNotifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends BaseUser
{
	static $deletingRelationships = [];

	use SoftDeletes;

    use AccountManagerUserPermissionsTrait;

    use CRUDModelTrait;
    use CRUDRelationshipModelTrait;

    public function getShortPrivacyName()
    {
    	$pieces = explode(" ", $this->name);


    	return implode(" ", [
    		$pieces[0],
    		isset($pieces[1])? substr($pieces[1], 0, 1) : ''
    	]);
    }
    // use ExtendedNotifiable;
}
