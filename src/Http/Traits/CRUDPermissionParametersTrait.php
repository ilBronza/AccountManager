<?php

namespace IlBronza\AccountManager\Http\Traits;

trait CRUDPermissionParametersTrait
{
    public static $tables = [

        'index' => [
            'fields' => 
            [
                'mySelfEdit' => 'links.edit',
                'mySelfSee' => 'links.see',
                'name' => 'flat',
                'guard_name' => 'flat',
                'roles' => 'relations.belongsToMany'
            ]
        ]
    ];

    static $formFields = [
        'common' => [
            'default' => [
                'name' => ['text' => 'string|required|max:255'],
                'guard_name' => ['text' => 'string|required|max:255'],
                'roles' => [
                    'type' => 'select',
                    'multiple' => true,
                    'rules' => 'array|nullable|exists:roles,id',
                    'relation' => 'roles'
                ],
            ]
        ],
    ];    
}