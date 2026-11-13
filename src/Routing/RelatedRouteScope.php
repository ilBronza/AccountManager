<?php

namespace IlBronza\AccountManager\Routing;

use IlBronza\AccountManager\Models\Route;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RelatedRouteScope
{
    public function __construct(public Route $anchor, public array $options, public string $scope)
    {
    }

    public static function resolve(string $name, ?string $scope = null): self
    {
        $live = collect(app('router')->getRoutes()->getRoutes())->first(fn ($route) => $route->getName() === $name);
        abort_unless($live && in_array('GET', $live->methods(), true), 404);
        abort_if(app(RouteAccess::class)->isExcluded($name, $live->uri()), 404);
        $model = config('accountmanager.models.route.class', Route::class);
        $anchor = $model::query()->where('is_registered', true)->where('name', $name)->firstOrFail();
        $options = [];
        $prefix = $name;
        while (($position = strrpos($prefix, '.')) !== false) {
            $prefix = substr($prefix, 0, $position);
            if ($prefix !== '') $options['name:'.$prefix.'.'] = __('accountmanager::route-access.name_prefix', ['prefix' => $prefix.'.*']);
        }
        $segments = array_values(array_filter(explode('/', trim($anchor->uri, '/')), fn ($part) => $part !== ''));
        if (count($segments) > 1 && in_array(end($segments), ['create', 'edit'], true)) array_pop($segments);
        while ($segments) {
            $prefix = implode('/', $segments);
            $options['uri:'.$prefix] = __('accountmanager::route-access.uri_prefix', ['prefix' => '/'.$prefix]);
            array_pop($segments);
        }
        if (! $options) $options['current'] = __('accountmanager::route-access.current_only');
        $scope ??= array_key_first($options);
        if (! array_key_exists($scope, $options)) {
            throw ValidationException::withMessages(['scope' => __('accountmanager::route-access.invalid_selection')]);
        }

        return new self($anchor, $options, $scope);
    }

    public function contains(Route $route): bool
    {
        if ($route->domain !== $this->anchor->domain) return false;
        if ($this->scope === 'current') return $route->getKey() === $this->anchor->getKey();
        [$type, $prefix] = explode(':', $this->scope, 2);

        return $type === 'name'
            ? ($route->name !== null && str_starts_with($route->name, $prefix))
            : ($route->uri === $prefix || str_starts_with($route->uri, $prefix.'/'));
    }

    public function routes(): Collection
    {
        return $this->anchor->newQuery()->with(['roles', 'permissions'])->where('is_registered', true)
            ->where('domain', $this->anchor->domain)->get()->filter(fn ($route) => $this->contains($route))->values();
    }
}
