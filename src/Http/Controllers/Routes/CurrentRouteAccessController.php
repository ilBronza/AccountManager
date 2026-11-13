<?php

namespace IlBronza\AccountManager\Http\Controllers\Routes;

use IlBronza\AccountManager\Helpers\RouteSyncHelper;
use IlBronza\AccountManager\Models\Route;
use IlBronza\AccountManager\Routing\RouteAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CurrentRouteAccessController
{
    public function show(Request $request): JsonResponse
    {
        $input = $request->validate(['route_name' => ['required', 'string', 'max:255']]);
        $this->checkRoute($input['route_name']);
        RouteSyncHelper::sync();
        $route = $this->record($input['route_name'])->load(['roles', 'permissions']);

        return response()->json([
            'route_name' => $route->name,
            'related_url' => route(config('accountmanager.routePrefix').'routes.related', ['related_to' => $route->name, 'iframed' => 'true']),
            'description' => $route->description,
            'system_roles' => array_values(array_diff($route->system_roles ?? [], app(RouteAccess::class)->bypassRoles())),
            'roles' => $this->choices('role'),
            'permissions' => $this->choices('permission'),
            'selected_roles' => $route->roles->whereNotIn('name', app(RouteAccess::class)->bypassRoles())->values()->modelKeys(),
            'selected_permissions' => $route->permissions->modelKeys(),
        ])->header('Cache-Control', 'no-store');
    }

    public function update(Request $request): JsonResponse
    {
        $input = $request->validate([
            'route_name' => ['required', 'string', 'max:255'],
            'roles' => ['present', 'array', 'max:1000'],
            'roles.*' => ['required', 'integer', 'distinct'],
            'permissions' => ['present', 'array', 'max:1000'],
            'permissions.*' => ['required', 'integer', 'distinct'],
        ]);
        $this->checkRoute($input['route_name']);
        foreach (['role' => 'roles', 'permission' => 'permissions'] as $type => $key) {
            $query = $type === 'role' ? app(RouteAccess::class)->assignableRolesQuery()
                : app(config('permission.models.'.$type))->newQuery();
            if ($query->whereKey($input[$key])->count() !== count($input[$key])) {
                throw ValidationException::withMessages([$key => __('accountmanager::route-access.invalid_selection')]);
            }
        }

        $route = $this->record($input['route_name']);
        $route->getConnection()->transaction(function () use ($route, $input) {
            $route->newQuery()->whereKey($route->getKey())->lockForUpdate()->firstOrFail();
            // Bypass roles are outside this editor's scope, including any historical links.
            $implicitRoleIds = $route->roles()->whereIn('name', app(RouteAccess::class)->bypassRoles())->get()->modelKeys();
            $route->roles()->sync(array_merge($implicitRoleIds, $input['roles']));
            $route->permissions()->sync($input['permissions']);
        });

        return response()->json(['message' => __('accountmanager::route-access.current_saved')]);
    }

    private function checkRoute(string $name): void
    {
        // Consult the live router as well as the catalog, which may contain stale records.
        $route = collect(app('router')->getRoutes()->getRoutes())->first(fn ($route) => $route->getName() === $name);
        abort_unless($route && in_array('GET', $route->methods(), true), 404);
        abort_if(app(RouteAccess::class)->isExcluded($name, $route->uri()), 404);
    }

    private function record(string $name): Route
    {
        $model = config('accountmanager.models.route.class', Route::class);

        return $model::query()->where('name', $name)->where('is_registered', true)->firstOrFail();
    }

    private function choices(string $type): array
    {
        $query = $type === 'role' ? app(RouteAccess::class)->assignableRolesQuery()
            : app(config('permission.models.'.$type))->newQuery();

        return $query->orderBy('name')->orderBy('guard_name')->get()
            ->map(fn ($model) => ['id' => $model->getKey(), 'name' => $model->name, 'guard' => $model->guard_name])->all();
    }
}
