<?php

namespace IlBronza\AccountManager\Http\Controllers;

use IlBronza\AccountManager\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GithubLoginController
{
    public function __invoke(Request $request)
    {
        try {
            $github = Socialite::driver('github')->user();
        } catch (InvalidStateException $exception) {
            abort(419, 'Invalid OAuth state. Please sign in again.');
        }

        abort_unless($github->getId(), 422, 'GitHub did not provide an account ID.');
        $model = User::getProjectClassName();
        $user = (new $model)->getConnection()->transaction(function () use ($model, $github) {
            $user = $model::withInactive()->withTrashed()->where('github_id', $github->getId())->first();
            if (! $user) {
                // Socialite's GitHub provider returns the primary, verified email.
                abort_unless(filter_var($github->getEmail(), FILTER_VALIDATE_EMAIL), 422, 'A verified GitHub email is required.');
                $user = $model::withInactive()->withTrashed()->where('email', $github->getEmail())->first();
                abort_if($user && $user->github_id && (string) $user->github_id !== (string) $github->getId(), 403);
            }

            if ($user) {
                abort_if(! $user->active || $user->trashed(), 403, 'This account is unavailable.');
            } else {
                $user = new $model;
                $user->name = $github->getName() ?: $github->getEmail();
                $user->email = $github->getEmail();
                $user->password = Hash::make(Str::random(64));
                $user->active = true;
            }

            $user->github_id = $github->getId();
            // Tokens are used for sign-in only; do not persist provider credentials.
            $user->github_token = null;
            $user->github_refresh_token = null;
            $user->save();

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(config('accountmanager.loginRedirect', '/home'));
    }
}
