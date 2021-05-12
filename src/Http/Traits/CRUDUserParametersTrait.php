<?php

namespace IlBronza\AccountManager\Http\Traits;

trait CRUDUserParametersTrait
{
    public static $tables = [

        'index' => [
            'fields' => 
            [
                'created_at' => [
                    'type' => 'dates.date',
                    'filterRange' => true
                ],
                'mySelfEdit' => 'links.edit',
                'mySelfSee' => 'links.see',
                'name' => [
                    'type' => 'flat',
                    'filterRange' => 'alphabetical'
                ],
                'email' => 'flat',
                'roles' => [
                    'type' => 'relations.belongsToMany',
                    'allowedForRoles' => ['administrator']
                ],
                'permissions' => [
                    'type' => 'relations.belongsToMany',
                    'allowedForRoles' => ['administrator']
                ],

                // 'cities' => 'relations.hasMany',
                // 'mySelfCallableCount.cities' => [
                //     'type' => 'iterators.each',
                //     'childType' => '_fn_getCallableContactsCount'
                // ],
                // 'mySelfNeverCalledCount.cities' => [
                //     'type' => 'iterators.each',
                //     'childType' => '_fn_getNeverCalledContactsCount'
                // ],
                // 'system' => 'flat',
                'mySelfDelete' => 'links.delete'
            ]
        ]
    ];

    static $formFields = [
        'common' => [
            'default' => [
                'fields' => [
                    'name' => ['text' => 'string|required|max:191'],
                    'email' => ['email' => 'email|required|max:191'],
                    'roles' => [
                        'type' => 'select',
                        'multiple' => true,
                        'rules' => 'array|nullable|exists:roles,id',
                        'relation' => 'roles',
                        'roles' => ['asdqwd']
                    ],
                    'permissions' => [
                        'type' => 'select',
                        'multiple' => true,
                        'rules' => 'array|nullable|exists:permissions,id',
                        'relation' => 'permissions'
                    ],
                ],
            ]
        ],
        'create' => [
            'default' => [
                'password' => ['password' => 'string|required|confirmed|max:191'],
                'password_confirmation' => ['password' => 'string|required'],
            ]
        ],
    ];    
}