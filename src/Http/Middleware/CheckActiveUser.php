<?php

namespace IlBronza\AccountManager\Http\Middleware;


use Auth;
use Closure;
use IlBronza\Ukn\Facades\Ukn;
use Illuminate\Http\Request;

class CheckActiveUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(Auth::check() && (Auth::user()->active == 0))
        {
            Ukn::e(__('accountManager.userNotActive'));
            
            Auth::logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['error' => 'Your Account is suspended, please contact Admin.']);

        }
            return $next($request);
    }
}
