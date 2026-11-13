<?php

namespace IlBronza\AccountManager\Routing;

use IlBronza\AccountManager\Http\Middleware\ManageRouteAccess;
use IlBronza\CRUD\Middleware\CRUDBasePackageMiddlewareRolesPermissions;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use ReflectionMethod;

class RouteSystemRoles
{
    public function __construct(private Router $router)
    {
    }

    /** Role names declared by the existing middleware, independently of catalog assignments. */
    public function forRoute(Route $route): array
    {
        $roles = [];
        // Resolve aliases, groups and controller middleware, respecting withoutMiddleware().
        foreach ($this->router->gatherRouteMiddleware($route) as $middleware) {
            if (! is_string($middleware)) continue;
            [$class, $parameters] = array_pad(explode(':', $middleware, 2), 2, '');

            if (is_a($class, 'Spatie\\Permission\\Middleware\\RoleMiddleware', true)
                || is_a($class, 'Spatie\\Permission\\Middlewares\\RoleMiddleware', true)) {
                $roles = array_merge($roles, explode('|', explode(',', $parameters, 2)[0]));
            } elseif (is_a($class, CRUDBasePackageMiddlewareRolesPermissions::class, true)) {
                $request = Request::create('/');
                $request->setRouteResolver(fn () => $route);
                // Reuse the package's protected resolver, including overrides, without running handle().
                $resolver = new ReflectionMethod($class, 'getRolesForCurrentRoute');
                $roles = array_merge($roles, $resolver->invoke(app($class), $request));
            } elseif (is_a($class, ManageRouteAccess::class, true)) {
                $roles = array_merge($roles, app(RouteAccess::class)->managementRoles());
            }
        }

        return array_values(array_unique(array_filter(array_map('trim', $roles),
            static fn (string $role) => $role !== '')));
    }
}
