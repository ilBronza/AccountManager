<?php

namespace IlBronza\AccountManager\Http\Parameters\TableFields;

use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class UserTableFieldsParameters extends FieldsGroupParametersFile
{
	static function getFieldsGroup() : array
	{
		return [
            'translationPrefix' => 'accountmanager',
            'fields' => [
                'created_at' => [
                    'type' => 'dates.date',
                    'filterRange' => true
                ],
                'mySelfEdit' => 'links.edit',
                'name' => [
                    'type' => 'flat',
                    'filterRange' => 'alphabetical'
                ],
                'active' => 'editor.toggle',
                'email' => 'flat',
                'roles' => [
                    'type' => 'relations.belongsToMany',
                    'allowedForRoles' => ['superadmin', 'administrator'],
                ],
                'mySelfDuplicate' => [
                    'type' => 'links.link',
                    'function' => 'getDuplicateUrl',
                    'faIcon' => 'copy',
                    'roles' => ['superadmin']
                ],
                'permissions' => [
                    'type' => 'relations.belongsToMany',
                    'allowedForRoles' => ['superadmin', 'administrator']
                ],

                'mySelfDelete' => 'links.delete'
            ]
        ];
	}
}