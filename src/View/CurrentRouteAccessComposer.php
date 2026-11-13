<?php

namespace IlBronza\AccountManager\View;

use IlBronza\AccountManager\Routing\RouteAccess;
use Illuminate\View\View;

class CurrentRouteAccessComposer
{
    public function compose(View $view): void
    {
        if (! config('accountmanager.routeAccess.enabled', false)
            || ! app(RouteAccess::class)->canManage(request()->user())) return;

        $route = request()->route();
        if (! $route) return;
        if ($route->getName() === config('accountmanager.routePrefix').'routes.related' && request()->boolean('iframed')) return;

        $extraViews = $view->getData()['extraViews'] ?? view()->shared('extraViews', []);
        $extraViews['bottom']['accountmanager::routes.current-access'] = [
            'currentRouteName' => $route->getName(),
            'currentRoutePath' => implode('|', $route->methods()).' /'.$route->uri(),
            'currentRouteNotice' => $route->getName() === null
                ? __('accountmanager::route-access.unnamed')
                : (app(RouteAccess::class)->isExcluded($route->getName(), $route->uri())
                    ? __('accountmanager::route-access.excluded_help') : null),
        ];
        $view->with('extraViews', $extraViews);
    }
}
