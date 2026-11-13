<?php

namespace IlBronza\AccountManager\Routing;

use IlBronza\UikitTemplate\Helpers\RouteTitleTranslator;
use Illuminate\Routing\Router;

class RouteNameTranslations
{
    private array $routes = [];

    public function __construct(Router $router)
    {
        // Fluent name() calls may not yet be present in the collection's name lookup.
        foreach ($router->getRoutes() as $route) {
            if (($name = $route->getName()) !== null) $this->routes[$name] = $route;
        }
    }

    public function forName(?string $name): ?string
    {
        if ($name === null || ! isset($this->routes[$name])) return null;

        $prefix = RouteTitleTranslator::getTranslationPrefix($this->routes[$name]);
        foreach (RouteTitleTranslator::candidateItems($name) as $item) {
            $key = $prefix.$item;
            $translation = trans($key);
            if (is_string($translation) && $translation !== $key) return $translation;
        }

        return null;
    }
}
