<?php

namespace IlBronza\AccountManager\Http\Middleware;

use Closure;
use IlBronza\AccountManager\Models\Route;
use IlBronza\AccountManager\Routing\RouteAccess;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class AuthorizeRouteAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('accountmanager.routeAccess.enabled', false)) return $next($request);

        $access = app(RouteAccess::class);
        $current = $request->route();
        if ($current && $access->isExcluded($current->getName(), $current->uri())) return $next($request);

        $user = $request->user();
        if ($access->bypasses($user)) {
            return $next($request);
        }
        // Managers must be able to configure access even with an empty catalog.
        if ($current && $access->canManage($user)
            && in_array(ManageRouteAccess::class, app('router')->gatherRouteMiddleware($current), true)) {
            return $next($request);
        }

        $name = $request->route()?->getName();
        $formName = $access->correspondingFormRouteName($name);
        $model = config('accountmanager.models.route.class', Route::class);
        $routes = $name === null ? collect() : $model::query()->with(['roles', 'permissions'])
            ->whereIn('name', $formName === null ? [$name] : [$name, $formName])
            ->where('is_registered', true)->get()->keyBy('name');
        $route = $routes->get($name);
        // A hidden write route uses the form's rules, including an empty (deny-all) rule set.
        if ($route && $formName !== null) $route = $routes->get($formName, $route);

        if (! $route || ($route->roles->isEmpty() && $route->permissions->isEmpty())) {
            abort(403);
        }

        if (! $user) throw new AuthenticationException;

        // Pass models to Spatie so identically named roles on different guards cannot match.
        foreach ($route->roles as $role) {
            if ($user->hasRole($role)) return $next($request);
        }
        foreach ($route->permissions as $permission) {
            if ($user->checkPermissionTo($permission)) return $next($request);
        }

        abort(403);
    }
}
