<?php

namespace IlBronza\AccountManager\Http\Controllers\Routes;

use IlBronza\AccountManager\Models\Route;
use IlBronza\AccountManager\Routing\RouteAccess;
use IlBronza\AccountManager\Routing\RelatedRouteScope;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AssociateRouteController
{
    public function __invoke(Request $request)
    {
        $input = $request->validate([
            'route_ids' => ['required', 'string', 'json'],
            'operation' => ['required', 'in:attach,detach'],
            'roles' => ['sometimes', 'array', 'max:1000'], 'roles.*' => ['required', 'integer', 'distinct'],
            'permissions' => ['sometimes', 'array', 'max:1000'], 'permissions.*' => ['required', 'integer', 'distinct'],
            'related_to' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'max:2048'],
        ]);
        $ids = json_decode($input['route_ids'], true, 512, JSON_THROW_ON_ERROR);
        validator(['ids' => $ids], ['ids' => ['required', 'array', 'min:1', 'max:20000'],
            'ids.*' => ['required', 'integer', 'distinct']])->validate();
        $roles = $input['roles'] ?? [];
        $permissions = $input['permissions'] ?? [];
        $related = isset($input['related_to']) ? RelatedRouteScope::resolve($input['related_to'], $input['scope'] ?? null) : null;
        if (! $roles && ! $permissions) {
            throw ValidationException::withMessages(['roles' => __('accountmanager::route-access.choose_targets')]);
        }
        foreach (['role' => $roles, 'permission' => $permissions] as $type => $keys) {
            $query = $type === 'role' ? app(RouteAccess::class)->assignableRolesQuery()
                : app(config('permission.models.'.$type))->newQuery();
            if ($query->whereKey($keys)->count() !== count($keys)) {
                throw ValidationException::withMessages([$type.'s' => __('accountmanager::route-access.invalid_selection')]);
            }
        }

        $model = config('accountmanager.models.route.class', Route::class);
        (new $model)->getConnection()->transaction(function () use ($model, $ids, $roles, $permissions, $input, $related) {
            $routes = $model::query()->whereKey($ids)->where('is_registered', true)->whereNotNull('name')->lockForUpdate()->get();
            if ($routes->count() !== count($ids) || $routes->contains(fn ($route) => app(RouteAccess::class)->isExcluded($route->name, $route->uri))) {
                throw ValidationException::withMessages(['route_ids' => __('accountmanager::route-access.invalid_selection')]);
            }
            if ($related && $routes->contains(fn ($route) => ! $related->contains($route))) {
                throw ValidationException::withMessages(['route_ids' => __('accountmanager::route-access.invalid_selection')]);
            }
            foreach ($routes as $route) {
                foreach (['roles' => $roles, 'permissions' => $permissions] as $relation => $keys) {
                    $input['operation'] === 'attach' ? $route->$relation()->syncWithoutDetaching($keys) : $route->$relation()->detach($keys);
                }
            }
        });

        if ($related) {
            return redirect()->route(config('accountmanager.routePrefix').'routes.related', [
                'related_to' => $related->anchor->name, 'scope' => $related->scope, 'iframed' => 'true',
            ])->with('status', __('accountmanager::route-access.saved', ['count' => count($ids)]))
                ->with('routeAccessRelatedUpdated', $related->anchor->name);
        }

        return redirect()->route(config('accountmanager.routePrefix').'routes.index')
            ->with('status', __('accountmanager::route-access.saved', ['count' => count($ids)]));
    }
}
