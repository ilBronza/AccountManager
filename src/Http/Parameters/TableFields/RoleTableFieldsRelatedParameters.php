<?php

namespace IlBronza\AccountManager\Http\Parameters\TableFields;

use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class RoleTableFieldsRelatedParameters extends FieldsGroupParametersFile
{
	static function getFieldsGroup() : array
	{
		return [
            'translationPrefix' => 'accountmanager::fields',
            'fields' => [
                'mySelfSee' => 'links.see',
                'name' => 'flat',
                'guard_name' => 'flat',
                'permissions' => 'relations.belongsToMany',
            ]
        ];
	}
}