<?php

namespace IlBronza\AccountManager\Http\Middleware;

use IlBronza\CRUD\Middleware\CRUDBasePackageMiddlewareRolesPermissions;

/**
 * Resolves allowed roles for AccountManager routes from config (accountmanager.defaultRoles / accountmanager.routeRoles).
 */
class AccountManagerMiddlewareRolesPermissions extends CRUDBasePackageMiddlewareRolesPermissions
{
    protected string $configPackageName = 'accountmanager';
}
