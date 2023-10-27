<?php

namespace IlBronza\AccountManager\Http\Traits;

trait CRUDRoleParametersTrait
{
    static $formFields = [
        'common' => [
            'default' => [
                'name' => ['text' => 'string|required|max:255'],
                'guard_name' => ['text' => 'string|required|max:255'],
                'permissions' => [
                    'type' => 'select',
                    'multiple' => true,
                    'rules' => 'array|nullable|exists:permissions,id',
                    'relation' => 'permissions'
                ],
            ]
        ],
    ];    
}