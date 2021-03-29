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
                'roles' => 'relations.hasMany',
                'permissions' => 'relations.hasMany',
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
        ],
 
        'report' => [
            'fields' => 
            [
                'mySelfReport' => [
                    'view' => 'button',
                    'button' => 'getReportButton'
                ],
                'name' => 'flat',
                'cities' => 'many',
                'calls_count' => 'flat',
                'appointment_calls_count' => 'flat',
                'missed_calls_count' => 'flat',
                'bad_calls_count' => 'flat'
            ]
        ]
    ];

    static $formFields = [
        'common' => [
            'default' => [
                'name' => ['text' => 'string|required|max:191'],
                'email' => ['email' => 'email|required|max:191'],
                'roles' => [
                    'type' => 'select',
                    'multiple' => true,
                    'rules' => 'array|required|exists:roles,id',
                    'relation' => 'roles'
                ],
                'permissions' => [
                    'type' => 'select',
                    'multiple' => true,
                    'rules' => 'array|nullable|exists:permissions,id',
                    'relation' => 'permissions'
                ],
            ]
        ],
        'create' => [
            'default' => [
                'password' => ['text' => 'string|required|confirmed|max:191'],
                'password_confirmation' => ['text' => 'string|required'],
            ]
        ],
    ];    
}