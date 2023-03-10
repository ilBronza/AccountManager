<?php

use App\Models\ProjectSpecific\User;

return [
    'user' => [
        'class' => User::class
    ],

	'indexFields' => [
		'fields' => [
            'created_at' => [
                'type' => 'dates.date',
                'filterRange' => true
            ],
            'mySelfEdit' => 'links.edit',
            'name' => [
                'type' => 'flat',
                'filterRange' => 'alphabetical'
            ],
            'active' => 'editor.toggle',
            'email' => 'flat',
            'roles' => [
                'type' => 'relations.belongsToMany',
                'allowedForRoles' => ['superadmin', 'administrator'],
            ],
            'mySelfDuplicate' => [
                'type' => 'links.link',
                'function' => 'getDuplicateUrl',
                'faIcon' => 'copy',
                'roles' => ['superadmin']
            ],
            'permissions' => [
                'type' => 'relations.belongsToMany',
                'allowedForRoles' => ['superadmin', 'administrator']
            ],

            'mySelfDelete' => 'links.delete'
		]
	],


	'formFields' => [
        'common' => [
            'default' => [
                'fields' => [
                    'name' => ['text' => 'string|required|max:191'],
                    'email' => ['email' => 'email|required|max:191'],
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
                ]
            ]
        ],
        'create' => [
            'default' => [
                'fields' => [
                    'password' => [
                        'type' => 'password',
                        'rules' => 'string|required|confirmed|max:191',
                        'roles' => ['superadmin', 'administrator']
                    ],
                    'password_confirmation' => [
                        'type' => 'password',
                        'rules' => 'string|required',
                        'roles' => ['superadmin', 'administrator']
                    ]
                ]
            ]
        ],
        'edit' => [
            'default' => [
                'fields' => [
                    'password' => [
                        'type' => 'password',
                        'rules' => 'string|nullable|confirmed|max:191',
                        'roles' => ['superadmin', 'administrator']
                    ],
                    'password_confirmation' => [
                        'type' => 'password',
                        'rules' => 'string|nullable',
                        'roles' => ['superadmin', 'administrator']
                    ]
                ]
            ]
        ]
    ]
];