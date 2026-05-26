<?php

use App\Models\ProjectSpecific\User;
use IlBronza\AccountManager\Http\Controllers\Account\EditAccountController;
use IlBronza\AccountManager\Http\Controllers\Permissions\PermissionController;
use IlBronza\AccountManager\Http\Controllers\Roles\RoleController;
use IlBronza\AccountManager\Http\Controllers\Userdata\AdminUserDataController;
use IlBronza\AccountManager\Http\Controllers\Userdata\EditUserDataAvatarController;
use IlBronza\AccountManager\Http\Controllers\Userdata\EditUserDataController;
use IlBronza\AccountManager\Http\Controllers\Userdata\UserDataDeleteMediaController;
use IlBronza\AccountManager\Http\Controllers\AccessLogs\IndexUserAccessLogController;
use IlBronza\AccountManager\Http\Controllers\Users\CreateSlimUserController;
use IlBronza\AccountManager\Http\Controllers\Users\CreateUserController;
use IlBronza\AccountManager\Http\Controllers\Users\DestroyUserController;
use IlBronza\AccountManager\Http\Controllers\Users\DuplicateUserController;
use IlBronza\AccountManager\Http\Controllers\Users\EditUserController;
use IlBronza\AccountManager\Http\Controllers\Users\IndexUserController;
use IlBronza\AccountManager\Http\Controllers\Users\TrashedIndexUserController;
use IlBronza\AccountManager\Http\Controllers\Users\ShowUserController;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\RoleFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\UserAccountEditFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\UserCreateFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\UserCreateSlimFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\UserEditFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\UserShowFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\UserdataAvatarEditFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\UserdataEditFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\RelationshipsManagers\UserRelationshipsManager;
use IlBronza\AccountManager\Http\Parameters\TableFields\PermissionTableFieldsParameters;
use IlBronza\AccountManager\Http\Parameters\TableFields\RoleTableFieldsParameters;
use IlBronza\AccountManager\Http\Parameters\TableFields\RoleTableFieldsRelatedParameters;
use IlBronza\AccountManager\Http\Parameters\TableFields\UserAccessLogTableFieldsParameters;
use IlBronza\AccountManager\Http\Parameters\TableFields\UserTableFieldsParameters;
use IlBronza\AccountManager\Models\Permission;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\UserAccessLog;
use IlBronza\AccountManager\Models\Userdata;

return [
    'manzato' => 'effet',
    'enabled' => false,
    'usesUserdata' => false,
    'canResetPassword' => false,
    'usesUserdata' => true,
    'usesAvatar' => true,
    'accountmanager' => true,

    'ipAccess' => [
        /**
         * Default internal networks. Override per-project if needed.
         * Supports single IPs and CIDR ranges (IPv4/IPv6).
         */
        'internalCidrs' => [
            '127.0.0.1/32',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '::1/128',
            'fc00::/7',
            'fe80::/10',
        ],
    ],

    /**
     * Log page views (non-AJAX) for authenticated users.
     * Laravel 10: aggiungi in App\Http\Kernel il middleware alla fine dell’array `$middlewareGroups['web']`.
     * Laravel 11+: `appendToGroup('web', \IlBronza\AccountManager\Http\Middleware\LogUserAccess::class)` in bootstrap/app.php.
     */
    'logUserAccess' => [
        'enabled' => true,
        /**
         * Connessione database per la tabella user_access_logs (deve esistere in config/database.php).
         */
        'connection' => 'activityMysql',
    ],

    /**
     * POST senza middleware (né web/auth/role): il client invia user_id nel body.
     * Cache key: heartbeat-{user_id}, TTL in secondi.
     */
    'heartbeat' => [
        'enabled' => true,
        'ttl_seconds' => 60,
        /** Invio client: resta sotto il TTL (es. 30s con TTL 60s). */
        'interval_seconds' => 30,
        'uri' => 'account-management/heartbeat',
    ],

    'trashedUsers' => true,

	'defaultAvatar' => 'https://randomuser.me/api/portraits/men/97.jpg',

    'routePrefix' => 'accountmanager',

    /***
     * 
     * ATTENZIONE, aggiungere ruoli solo in-app, sennò apre a tutti
     * 
     ***/
    'defaultRoles' => [
        'superadmin' => true,
        'accountManagerUsers' => true,
    ],

    'routeRoles' => [
        'accountmanagerusers' => [
            'index' => ['superadmin', 'accountManagerUsers'],
            'create' => ['superadmin', 'accountManagerUsers'],
            'store' => ['superadmin', 'accountManagerUsers'],
            'show' => ['superadmin', 'accountManagerUsers'],
            'edit' => ['superadmin', 'accountManagerUsers'],
            'update' => ['superadmin', 'accountManagerUsers'],
            'destroy' => ['superadmin', 'accountManagerUsers'],
        ],
        'accountmanagerroles' => [
            'index' => ['superadmin', 'accountManagerUsers'],
            'create' => ['superadmin'],
            'store' => ['superadmin'],
            'show' => ['superadmin'],
            'edit' => ['superadmin'],
            'update' => ['superadmin'],
            'destroy' => ['superadmin'],
        ],
        'accountmanagerpermissions' => [
            'index' => ['superadmin', 'accountManagerUsers'],
            'create' => ['superadmin'],
            'store' => ['superadmin'],
            'show' => ['superadmin'],
            'edit' => ['superadmin'],
            'update' => ['superadmin'],
            'destroy' => ['superadmin'],
        ]
    ],

	'fakeEmailDomain' => 'fake' . (str_replace('http://', '', str_replace('https://', '', env('APP_URL')))),
    'models' => [
        'userdata' => [
            'class' => Userdata::class,
            'table' => 'users__userdata',
            'controllers' => [
                'admin' => AdminUserDataController::class,
                'edit' => EditUserDataController::class,
                'update' => EditUserDataController::class,
                'editAvatar' => EditUserDataAvatarController::class,
                'updateAvatar' => EditUserDataAvatarController::class,
                'deleteMedia' => UserDataDeleteMediaController::class
            ],
            'parametersFiles' => [
                'edit' => UserdataEditFieldsetsParameters::class,
                'editAvatar' => UserdataAvatarEditFieldsetsParameters::class
            ],
        ],
        'role' => [
            'class' => Role::class,
            'table' => 'roles',
            'controller' => RoleController::class,
            'fieldsGroupsFiles' => [
                'index' => RoleTableFieldsParameters::class,
                'related' => RoleTableFieldsRelatedParameters::class,
            ],
	        'parametersFiles' => [
		        'create' => RoleFieldsetsParameters::class,
	        ],
        ],
        'permission' => [
            'class' => Permission::class,
            'table' => 'permissions',
            'controller' => PermissionController::class,
            'fieldsGroupsFiles' => [
                'index' => PermissionTableFieldsParameters::class,
                'related' => PermissionTableFieldsParameters::class,
            ],
        ],
        'accessLog' => [
            'class' => UserAccessLog::class,
            'table' => 'user_access_logs',
            'controllers' => [
                'index' => IndexUserAccessLogController::class,
            ],
            'fieldsGroupsFiles' => [
                'index' => UserAccessLogTableFieldsParameters::class,
            ],
        ],
        'user' => [
            'class' => User::class,
            'controllers' => [
                'editAccount' => EditAccountController::class,
                'updateAccount' => EditAccountController::class,
                'duplicate' => DuplicateUserController::class,
                'edit' => EditUserController::class,
                'update' => EditUserController::class,
                'show' => ShowUserController::class,
                'create' => CreateUserController::class,
                'store' => CreateUserController::class,
                'createSlim' => CreateSlimUserController::class,
                'storeSlim' => CreateSlimUserController::class,
                'destroy' => DestroyUserController::class,
                'index' => IndexUserController::class,
                'trashed' => TrashedIndexUserController::class
            ],
            'fieldsGroupsFiles' => [
                'index' => UserTableFieldsParameters::class
            ],
            'relationshipsManagerClasses' => [
                'show' => UserRelationshipsManager::class
            ],
            'parametersFiles' => [
                'editAccount' => UserAccountEditFieldsetsParameters::class,
                'create' => UserCreateFieldsetsParameters::class,
                'createSlim' => UserCreateSlimFieldsetsParameters::class,
                'edit' => UserEditFieldsetsParameters::class,
                'show' => UserShowFieldsetsParameters::class,
            ],
            'table' => 'users'
        ]
    ]
];