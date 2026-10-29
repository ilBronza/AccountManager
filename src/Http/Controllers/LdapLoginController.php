<?php

namespace IlBronza\AccountManager\Http\Controllers;

use IlBronza\AccountManager\Auth\Ldap\DirectoryAuthenticator;
use IlBronza\AccountManager\Auth\Ldap\LdapAccounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class LdapLoginController
{
    public function show()
    {
        abort_unless(config('accountmanager_ldap.enabled'), 404);

        return Auth::guard('web')->check()
            ? redirect(config('accountmanager.loginRedirect', '/home'))
            : view('accountmanager::auth.ldap-login');
    }

    public function login(Request $request)
    {
        abort_unless(config('accountmanager_ldap.enabled'), 404);
        if (Auth::guard('web')->check()) {
            return redirect(config('accountmanager.loginRedirect', '/home'));
        }

        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:4096'],
        ]);

        try {
            $identity = app(DirectoryAuthenticator::class)->authenticate($credentials['username'], $credentials['password']);
            $user = $identity ? app(LdapAccounts::class)->resolve($identity) : null;
        } catch (Throwable $exception) {
            // Do not log credentials, directory diagnostics or exception traces.
            Log::warning('LDAP sign-in unavailable.', ['exception_type' => get_class($exception)]);
            if ($request->expectsJson()) {
                return response()->json(['message' => __('accountmanager::ldap.unavailable')], 503);
            }

            return back()->withInput($request->only('username'))
                ->withErrors(['username' => __('accountmanager::ldap.unavailable')]);
        }

        if (! $user) {
            throw ValidationException::withMessages(['username' => __('accountmanager::ldap.failed')]);
        }

        // Use the existing session guard; no LDAP provider replaces local login.
        // Deliberately no remember cookie for the new directory login.
        Auth::guard('web')->login($user, false);
        $request->session()->regenerate();

        return redirect()->intended(config('accountmanager.loginRedirect', '/home'));
    }
}
