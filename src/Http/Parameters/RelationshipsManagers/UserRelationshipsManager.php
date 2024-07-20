<?php

namespace IlBronza\AccountManager\Http\Parameters\RelationshipsManagers;

use IlBronza\AccountManager\Http\Controllers\Permissions\PermissionController;
use IlBronza\AccountManager\Http\Controllers\Roles\RoleController;
use IlBronza\Addresses\Http\Controllers\CrudAddressController;
use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipsManager;

class UserRelationshipsManager Extends RelationshipsManager
{
	public  function getAllRelationsParameters() : array
	{
		$relations = [];

		if(config('operators.enabled'))
			$relations['operator'] = config('operators.models.operator.controllers.show');


		if(config('contacts.enabled'))
			$relations['contacts'] = config('contacts.models.contact.controllers.index');

		if(config('addresses.enabled'))
		{
			$relations['address'] = config('addresses.models.address.controllers.show');
			// $relations['addresses'] = CrudAddressController::class;
		}

		$relations['roles'] = RoleController::class;
		$relations['permissions'] = PermissionController::class;

		return [
			'show' => [
				'relations' => $relations
			]
		];
	}
}