<?php

namespace IlBronza\AccountManager\Http\Traits;

trait CRUDRoleParametersTrait
{
    public static $tables = [

        'index' => [
            'fields' => 
            [
                'mySelfEdit' => 'links.edit',
                'mySelfSee' => 'links.see',
                'name' => 'flat',
                'guard_name' => 'flat',
                'permissions' => 'relations.belongsToMany'
            ]
        ],
        'related' => [
            'fields' => 
            [
                'mySelfEdit' => 'links.edit',
                'mySelfSee' => 'links.see',
                'name' => 'flat',
                'guard_name' => 'flat',
                'permissions' => 'relations.belongsToMany'
            ]
        ]
    ];

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