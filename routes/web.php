<?php

use IlBronza\AccountManager\Models\User;
use IlBronza\Ukn\Ukn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

Route::group(['middleware' => ['web']], function () {
	Route::get('/auth/redirect', function ()
	{
		return Socialite::driver('github')->redirect();
	})->name('login.github');
	 
	Route::get('/auth/callback', function ()
	{
		$githubUser = Socialite::driver('github')->user();

		if(! $user = User::getProjectClassName()::where('github_id', $githubUser->id)->first())
			if(! $user = User::getProjectClassName()::where('email', $githubUser->email)->first())
			{
				$password = Str::random(8);

				$user = User::getProjectClassName()::make();
				$user->name = $githubUser->name ?? $githubUser->email;
				$user->email = $githubUser->email;
				$user->password = Hash::make($password);

				$user->active = true;

				Ukn::w('Your new password is ' . $password . '. Change it as soon as possible');
			}

		$user->github_token = $githubUser->token;
		$user->github_refresh_token = $githubUser->refreshToken;
		$user->save();

		\Auth::login($user);

		return redirect('/home');
	});
});





Route::group([
	'middleware' => ['web', 'auth'],
	'prefix' => 'account-management',
	'as' => config('accountmanager.routePrefix'),
	'routeTranslationPrefix' => 'accountmanager::routes.'
	],
	function()
	{
		Route::get('edit-account', [AccountManager::getController('user', 'editAccount'), 'edit'])->name('accountmanager.account');
		Route::put('update-account', [AccountManager::getController('user', 'updateAccount'), 'update'])->name('accountmanager.update');
		
		Route::group([
				'prefix' => 'userdata',
			],
			function()
			{
				Route::get('edit', [AccountManager::getController('userdata', 'edit'), 'edit'])
					->name('accountmanager.editUserdata');

				Route::put('update', [AccountManager::getController('userdata', 'update'), 'update'])
					->name('accountmanager.updateUserdata');

				Route::get('edit-avatar', [AccountManager::getController('userdata', 'editAvatar'), 'edit'])
					->name('accountmanager.editAvatar');
				Route::put('update-avatar', [AccountManager::getController('userdata', 'updateAvatar'), 'update'])
					->name('accountmanager.updateAvatar');

				Route::delete('delete-media/{media}', [AccountManager::getController('userdata', 'deleteMedia'), 'deleteMedia'])
					->name('userdatas.deleteMedia');

			});

			Route::resource('roles', AccountManager::getController('role'));
			Route::resource('permissions', AccountManager::getController('permission'));


		Route::get('logout', function(Request $request)
		{
			Auth::logout();

			$request->session()->invalidate();
			$request->session()->regenerateToken();

			return redirect('/');
		})->name('accountmanager.logout');

		Route::group([
			'prefix' => 'users',
			'middleware' => ['role:superadmin|administrator'],
			],
			function()
			{
				Route::get('duplicate/{user}', [AccountManager::getController('user', 'duplicate'), 'duplicate'])
					->name('accountmanager.duplicate');

				Route::get('', [AccountManager::getController('user', 'index'), 'index'])->name('users.index');
				Route::post('', [AccountManager::getController('user', 'store'), 'store'])->name('users.store');
				Route::get('create', [AccountManager::getController('user', 'create'), 'create'])->name('users.create');
				Route::get('{user}', [AccountManager::getController('user', 'show'), 'show'])->name('users.show');
				Route::get('{user}/edit', [AccountManager::getController('user', 'edit'), 'edit'])->name('users.edit');
				Route::put('{user}', [AccountManager::getController('user', 'update'), 'update'])->name('users.update');
				Route::delete('{user}', [AccountManager::getController('user', 'destroy'), 'destroy'])->name('users.destroy');
			});

		Route::group([
			'prefix' => 'userdata',
			'middleware' => ['role:superadmin|administrator'],
			],
			function()
			{
				Route::get('{userdata}', [AccountManager::getController('userdata', 'admin'), 'show'])->name('userdatas.show');
				Route::get('{user}/edit', [AccountManager::getController('userdata', 'admin'), 'edit'])->name('userdatas.edit');
				Route::put('{user}', [AccountManager::getController('userdata', 'admin'), 'update'])->name('userdatas.update');
			});

});