<?php

namespace IlBronza\AccountManager\Http\Middleware;

use Closure;
use IlBronza\AccountManager\Routing\RouteAccess;
use Illuminate\Http\Request;

class ManageRouteAccess
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(config('accountmanager.routeAccess.enabled', false), 404);
        abort_unless(app(RouteAccess::class)->canManage($request->user()), 403);

        return $next($request);
    }
}
