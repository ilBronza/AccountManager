<?php

namespace IlBronza\AccountManager\Http\Parameters\FieldsetsParameters;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class PermissionFieldsetsParameters extends FieldsetParametersFile
{
    public function _getFieldsetsParameters(): array
    {
        return ['base' => [
            'translationPrefix' => 'accountmanager::fields',
            'fields' => [
                'name' => ['text' => 'string|required|max:255'],
                'guard_name' => ['text' => 'string|required|in:web'],
                'roles' => [
                    'type' => 'select', 'multiple' => true,
                    'rules' => 'array|nullable|exists:roles,id', 'relation' => 'roles',
                ],
            ],
        ]];
    }
}
