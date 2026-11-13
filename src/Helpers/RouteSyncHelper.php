<?php

namespace IlBronza\AccountManager\Helpers;

use IlBronza\AccountManager\Models\Route;
use IlBronza\AccountManager\Routing\RouteDescriptions;
use IlBronza\AccountManager\Routing\RouteSystemRoles;
use Illuminate\Routing\Router;

class RouteSyncHelper
{
    public static function sync(?Router $router = null): int
    {
        $router ??= app('router');
        $descriptions = app(RouteDescriptions::class);
        $descriptions->load();
        $systemRoles = new RouteSystemRoles($router);
        $records = [];
        $names = [];
        foreach ($router->getRoutes() as $route) {
            $name = $route->getName();
            if ($name !== null && isset($names[$name])) {
                abort(409, 'Duplicate route name: '.$name);
            }
            if ($name !== null) $names[$name] = true;
            $methods = $route->methods();
            sort($methods);
            $records[] = [
                'name' => $name,
                'signature' => hash('sha256', json_encode([$name, $route->getDomain(), $route->uri(), $methods], JSON_THROW_ON_ERROR)),
                'domain' => $route->getDomain(),
                'uri' => $route->uri(),
                'methods' => $methods,
                'action' => $route->getActionName(),
                'description' => $descriptions->forRoute($route),
                'system_roles' => $systemRoles->forRoute($route),
                'is_registered' => true,
            ];
        }

        $model = config('accountmanager.models.route.class', Route::class);
        return (new $model)->getConnection()->transaction(function () use ($model, $records) {
            $model::query()->update(['is_registered' => false]);
            foreach ($records as $record) {
                $identity = $record['name'] === null
                    ? ['signature' => $record['signature']] : ['name' => $record['name']];
                $route = $model::query()->firstOrNew($identity);
                if (filled($route->description)) unset($record['description']);
                $route->fill($record)->save();
            }

            return count($records);
        });
    }
}
