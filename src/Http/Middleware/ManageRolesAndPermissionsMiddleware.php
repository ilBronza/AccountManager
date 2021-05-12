<?php

namespace IlBronza\AccountManager\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\Request;

class ManageRolesAndPermissionsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if(session('currentUser') !== null)
            return $next($request);

        if(! $currentUser = Auth::user())
            return $next($request);

        $currentUser->loadMissing('roles', 'permissions');

        $currentUser->roleNames = $currentUser->roles->pluck('name')->toArray();
        $currentUser->permissionNames = $currentUser->permissions->pluck('name')->toArray();

        session(['currentUser' => $currentUser]);

        return $next($request);
    }
}
