<?php

namespace IlBronza\AccountManager\Http\Parameters\FieldsetsParameters;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class RoleFieldsetsParameters extends FieldsetParametersFile
{
    public function _getFieldsetsParameters() : array
    {
        $result = [
            'base' => [
                'translationPrefix' => 'accountmanager::fields',
                'fields' => [
                    'name' => ['text' => 'string|required|max:255'],
                    'guard_name' => ['text' => 'string|required|max:255'],
                    'allow_from_remote' => [
                        'type' => 'boolean',
                        'rules' => 'boolean|required',
                    ],
                    'allowed_ips' => [
                        'type' => 'textarea',
                        'rules' => 'string|nullable',
                    ],
                    'permissions' => [
                        'type' => 'select',
                        'multiple' => true,
                        'rules' => 'array|nullable|exists:permissions,id',
                        'relation' => 'permissions'
                    ]
                ],
                'width' => ['1-2@m']
            ]
        ];

        if(! config('app.usesPermissions', true))
            unset($result['base']['fields']['permissions']);

        return $result;
    }
}
