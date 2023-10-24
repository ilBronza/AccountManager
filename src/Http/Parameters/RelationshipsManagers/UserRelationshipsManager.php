<?php

namespace IlBronza\AccountManager\Http\Parameters\RelationshipsManagers;

use IlBronza\AccountManager\Http\Controllers\RoleController;
use IlBronza\CRUD\Providers\RelationshipsManager;
use IlBronza\Notes\Http\Controllers\CrudNoteController;

class UserRelationshipsManager Extends RelationshipsManager
{
	public function getAllRelationsParameters()
	{
		return [
			'show' => [
				'relations' => [
					'roles' => RoleController::class
				]
			]
		];
	}
}