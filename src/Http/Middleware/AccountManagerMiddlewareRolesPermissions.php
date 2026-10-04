<?php

namespace IlBronza\AccountManager\Http\Middleware;

use IlBronza\CRUD\Middleware\CRUDBasePackageMiddlewareRolesPermissions;
use Illuminate\Http\Request;

class AccountManagerMiddlewareRolesPermissions extends CRUDBasePackageMiddlewareRolesPermissions
{
    protected string $configPackageName = 'accountmanager';

    protected function getRolesForCurrentRoute(Request $request): array
    {
        $name = $request->route()?->getName();
        $prefix = config('accountmanager.routePrefix', 'accountmanager');
        $canonical = $name && str_starts_with($name, $prefix)
            ? 'accountmanager' . substr($name, strlen($prefix)) : $name;
        $roles = config("accountmanager.routeRoles.{$name}");
        $roles ??= config("accountmanager.routeRoles.{$canonical}");

        // An explicitly empty rule denies access; only absent rules use defaults.
        return $this->normalizeRoles($roles ?? config('accountmanager.defaultRoles', []));
    }

    protected function normalizeRoles(array|string|null $roles): array
    {
        if (is_array($roles) && ! array_is_list($roles)) {
            $roles = array_keys(array_filter($roles, static fn ($enabled) => $enabled === true));
        }

        return parent::normalizeRoles($roles);
    }
}
