<?php

namespace IlBronza\AccountManager\Http\Parameters\TableFields;

use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class PermissionTableFieldsParameters extends FieldsGroupParametersFile
{
	static function getFieldsGroup() : array
	{
		return [
            'translationPrefix' => 'accountmanager',
            'fields' => [
                'mySelfEdit' => 'links.edit',
                'mySelfSee' => 'links.see',
                'name' => 'flat',
                'guard_name' => 'flat',
                'permissions' => 'relations.belongsToMany',

                'mySelfDelete' => 'links.delete'
            ]
        ];
	}
}