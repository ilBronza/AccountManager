<?php

namespace IlBronza\AccountManager\Routing;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class RouteAccess
{
    public function bypassRoles(): array
    {
        return (array) config('accountmanager.routeAccess.bypassRoles', [
            'superadmin', 'superadministrator', 'administrator',
        ]);
    }

    public function assignableRolesQuery(): Builder
    {
        return app(config('permission.models.role'))->newQuery()->whereNotIn('name', $this->bypassRoles());
    }

    public function managementRoles(): array
    {
        return array_values(array_unique(array_merge(
            $this->bypassRoles(),
            (array) config('accountmanager.routeAccess.managementRoles', ['superadmin', 'superadministrator', 'administrator', 'routesManager'])
        )));
    }

    public function canManage($user): bool
    {
        return $user && $user->hasAnyRole($this->managementRoles());
    }

    public function correspondingFormRouteName(?string $name): ?string
    {
        if ($name === null) return null;

        foreach (['.store' => '.create', '.update' => '.edit'] as $suffix => $formSuffix) {
            if (str_ends_with($name, $suffix)) {
                return substr($name, 0, -strlen($suffix)).$formSuffix;
            }
        }

        return null;
    }

    public function bypasses($user): bool
    {
        return $user && $user->hasAnyRole($this->bypassRoles());
    }

    public function isExcluded(?string $name, string $uri): bool
    {
        foreach ((array) config('accountmanager.routeAccess.exceptRoutes', []) as $pattern) {
            if ($name !== null && Str::is($pattern, $name)) return true;
        }
        foreach ((array) config('accountmanager.routeAccess.exceptUris', []) as $pattern) {
            if (Str::is($pattern, $uri)) return true;
        }

        return false;
    }
}
