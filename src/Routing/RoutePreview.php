<?php

namespace IlBronza\AccountManager\Routing;

use IlBronza\AccountManager\Models\Route as CatalogRoute;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteAction;
use Illuminate\Routing\ViewController;
use Illuminate\Support\Str;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RoutePreview
{
    private array $getRoutes;

    public function __construct(private Request $request)
    {
        $this->getRoutes = app('router')->getRoutes()->getRoutesByMethod()['GET'] ?? [];
    }

    public function canPreview(CatalogRoute $record): bool
    {
        $route = $this->page($record);

        return $route && RouteExampleParameters::forRoute($route) !== null;
    }

    public function exampleUrl(CatalogRoute $record): ?string
    {
        $route = $this->page($record);
        if (! $route) return null;

        $parameters = RouteExampleParameters::forRoute($route);
        if (! $parameters) return null;

        try {
            foreach ($parameters->samples() as $values) {
                $url = url()->toRoute($route, $values + ['iframed' => 'true'], true);
                if ($this->matchesTarget($route, $url)) return $url;
            }
        } catch (QueryException $exception) {
            // An optional package can register routes before its tables exist.
            report($exception);
        }

        return null;
    }

    private function matchesTarget(Route $route, string $url): bool
    {
        $target = Request::create($url, 'GET', [], [], [], $this->request->server->all());
        if ($target->getSchemeAndHttpHost() !== $this->request->getSchemeAndHttpHost()) return false;

        // A sampled slug can also be the literal path of a different GET action.
        try {
            $matched = app('router')->getRoutes()->match($target);
        } catch (HttpExceptionInterface) {
            return false;
        }

        return $matched->uri() === $route->uri() && $matched->getDomain() === $route->getDomain()
            && $matched->getName() === $route->getName();
    }

    private function page(CatalogRoute $record): ?Route
    {
        $route = $this->getRoutes[$record->domain.$record->uri] ?? null;
        if (! $route || ! $record->is_registered || ! in_array('GET', $record->methods, true)) return null;
        if ($route->getName() !== $record->name || $route->getActionName() !== $record->action) return null;
        if ($route->isFallback) return null;
        if (app(RouteAccess::class)->isExcluded($record->name, $record->uri)) return null;

        if ($route->getDomain()) {
            $hostPattern = $route->toSymfonyRoute()->compile()->getHostRegex();
            if (! preg_match($hostPattern, $this->request->getHost())) return null;
        }
        foreach ($route->middleware() as $middleware) {
            if (is_string($middleware) && Str::before($middleware, ':') === 'signed') return null;
        }

        return $this->isPageAction($route) ? $route : null;
    }

    private function isPageAction(Route $route): bool
    {
        $pages = ['index', 'show', 'edit', 'create', 'home', 'dashboard'];
        $controller = $route->getControllerClass();
        $uses = $route->getAction('uses');
        $action = $controller ? Str::afterLast($uses, '@') : 'Closure';
        if ($controller && (! method_exists($controller, $action) || ! (new ReflectionMethod($controller, $action))->isPublic())) return false;
        if (ltrim((string) $controller, '\\') === ViewController::class || in_array($action, $pages, true)) return true;
        if (in_array(Str::afterLast((string) $route->getName(), '.'), $pages, true)
            && in_array($action, ['Closure', '__invoke', 'execute'], true)) return true;

        // Custom actions can also explicitly declare that they render a view.
        if (RouteAction::containsSerializedClosure($route->getAction())) $uses = unserialize($uses)->getClosure();
        if ($action === 'Closure' && ! $uses instanceof \Closure) return false;
        $reflection = $action === 'Closure' ? new ReflectionFunction($uses) : new ReflectionMethod($controller, $action);
        $type = $reflection->getReturnType();
        return $type instanceof ReflectionNamedType && is_a($type->getName(), View::class, true);
    }
}
