<?php

namespace IlBronza\AccountManager\Http\Parameters\RelationshipsManagers;

use IlBronza\AccountManager\Http\Controllers\Roles\RoleController;
use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipsManager;

class UserRelationshipsManager Extends RelationshipsManager
{
	public function getAllRelationsParameters() : array
	{
		$result =  [
			'show' => [
				'relations' => [
					'roles' => RoleController::class,
				]
			]
		];

		if(app('contacts'))
			$result['show']['relations']['contacts'] = config('contacts.models.contact.controllers.index');

		return $result;
	}
}