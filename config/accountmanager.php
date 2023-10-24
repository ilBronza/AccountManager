<?php

use App\Models\ProjectSpecific\User;
use IlBronza\AccountManager\Http\Controllers\Account\EditAccountController;
use IlBronza\AccountManager\Http\Controllers\Permissions\PermissionController;
use IlBronza\AccountManager\Http\Controllers\Roles\RoleController;
use IlBronza\AccountManager\Http\Controllers\Users\CreateUserController;
use IlBronza\AccountManager\Http\Controllers\Users\DestroyUserController;
use IlBronza\AccountManager\Http\Controllers\Users\DuplicateUserController;
use IlBronza\AccountManager\Http\Controllers\Users\EditUserController;
use IlBronza\AccountManager\Http\Controllers\Users\IndexUserController;
use IlBronza\AccountManager\Http\Controllers\Users\ShowUserController;
use IlBronza\AccountManager\Http\Controllers\Userdata\EditUserDataController;
use IlBronza\AccountManager\Http\Controllers\Userdata\UserDataDeleteMediaController;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\UserAccountEditFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\UserCreateFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\UserEditFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\UserShowFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\UserdataEditFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\RelationshipsManagers\UserRelationshipsManager;
use IlBronza\AccountManager\Http\Parameters\TableFields\UserTableFieldsParameters;
use IlBronza\AccountManager\Models\Userdata;

return [
    'models' => [
        'userdata' => [
            'class' => Userdata::class,
            'table' => 'users__userdata',
            'controllers' => [
                'edit' => EditUserDataController::class,
                'update' => EditUserDataController::class,
                'deleteMedia' => UserDataDeleteMediaController::class
            ],
            'parametersFiles' => [
                'edit' => UserdataEditFieldsetsParameters::class,
            ],
        ],
        'role' => [
            'class' => Role::class,
            'controller' => RoleController::class,
        ],
        'permission' => [
            'class' => Permission::class,
            'controller' => PermissionController::class,
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
                'edit' => UserEditFieldsetsParameters::class,
                'show' => UserShowFieldsetsParameters::class,
            ],
            'table' => 'users'
        ]
    ]
];