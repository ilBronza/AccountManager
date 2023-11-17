<?php

namespace IlBronza\AccountManager\Http\Parameters\FieldsetsParameters;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class UserEditFieldsetsParameters extends FieldsetParametersFile
{
    public function _getFieldsetsParameters() : array
    {
        $result = [
            'base' => [
                'translationPrefix' => 'accountmanager::fields',
                'fields' => [
                    'name' => ['text' => 'string|required|max:191'],
                    'email' => ['email' => 'email|required|max:191'],
                    //'password' => ['required', 'confirmed', Rules\Password::defaults()],
                    'password' => ['password' => 'string|nullable|confirmed|max:191'],
                    'password_confirmation' => ['password' => 'string|nullable'],
                ],
                'width' => ['1-2@m']
            ],
            'roles' => [
                'translationPrefix' => 'accountmanager::fields',
                'fields' => [
                    'active' => [
                        'type' => 'boolean',
                        'rules' => 'boolean|required',
                        'roles' => ['superadmin', 'administrator']
                    ],
                    'roles' => [
                        'type' => 'select',
                        'multiple' => true,
                        'rules' => 'array|nullable|exists:roles,id',
                        'relation' => 'roles',
                        'roles' => ['superadmin', 'administrator']
                    ],
                    'permissions' => [
                        'type' => 'select',
                        'multiple' => true,
                        'rules' => 'array|nullable|exists:permissions,id',
                        'relation' => 'permissions',
                        'roles' => ['superadmin', 'administrator']
                    ]
                ],
                'width' => ['1-2@m']
            ]
        ];

        if(! config('app.usesPermissions', true))
            unset($result['roles']['fields']['permissions']);

        return $result;
    }
}
