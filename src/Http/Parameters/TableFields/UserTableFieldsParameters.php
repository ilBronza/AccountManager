<?php

namespace IlBronza\AccountManager\Http\Parameters\TableFields;

use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class UserTableFieldsParameters extends FieldsGroupParametersFile
{
	static function getFieldsGroup() : array
	{
        $result = [
            'translationPrefix' => 'accountmanager',
            'fields' => [
                'created_at' => [
                    'type' => 'dates.date',
                    'filterRange' => true
                ],
                'mySelfShow' => 'links.see',
                'mySelfEdit' => 'links.edit',
                'name' => [
                    'type' => 'flat',
                    'filterRange' => 'alphabetical'
                ],
                'active' => 'editor.toggle',
                'email' => [
                    'type' => 'flat',
                    'width' => '250px'
                ],
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

        if(! config('app.usesPermissions', true))
            unset($result['fields']['permissions']);

		return $result;
	}
}