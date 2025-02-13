<?php

use App\Models\ProjectSpecific\User;
use IlBronza\AccountManager\Http\Controllers\Account\EditAccountController;
use IlBronza\AccountManager\Http\Controllers\Permissions\PermissionController;
use IlBronza\AccountManager\Http\Controllers\Roles\RoleController;
use IlBronza\AccountManager\Http\Controllers\Userdata\AdminUserDataController;
use IlBronza\AccountManager\Http\Controllers\Userdata\EditUserDataAvatarController;
use IlBronza\AccountManager\Http\Controllers\Userdata\EditUserDataController;
use IlBronza\AccountManager\Http\Controllers\Userdata\UserDataDeleteMediaController;
use IlBronza\AccountManager\Http\Controllers\Users\CreateSlimUserController;
use IlBronza\AccountManager\Http\Controllers\Users\CreateUserController;
use IlBronza\AccountManager\Http\Controllers\Users\DestroyUserController;
use IlBronza\AccountManager\Http\Controllers\Users\DuplicateUserController;
use IlBronza\AccountManager\Http\Controllers\Users\EditUserController;
use IlBronza\AccountManager\Http\Controllers\Users\IndexUserController;
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
use IlBronza\AccountManager\Http\Parameters\TableFields\UserTableFieldsParameters;
use IlBronza\AccountManager\Models\Permission;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\Userdata;

return [
    'enabled' => false,
    'usesUserdata' => false,
    'canResetPassword' => false,
    'usesUserdata' => true,
    'usesAvatar' => true,
    'accountmanager' => true,

    'routePrefix' => 'accountmanager',
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
                'index' => IndexUserController::class
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