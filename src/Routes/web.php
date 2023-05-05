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
	'prefix' => 'account-management'
	],
	function()
	{
		Route::get('edit-account', [
			AccountManager::getController('editAccount'),
				'edit'
			])
		->name('accountManager.account');

		Route::put('update-account', [
			AccountManager::getController('editAccount'),
				'update'
			])
		->name('accountManager.update');


		Route::get('duplicate/{user}', [
			AccountManager::getController('duplicateAccount'),
			'duplicate'
		])
		->name('accountManager.duplicate');
		
		Route::get('restore/{user}', [
			AccountManager::getController('restoreAccount'),
			'restore'
		])
		->name('accountManager.restore');

		Route::group([
				'prefix' => 'userdata',
			],
			function()
			{
				Route::get('edit', [
					AccountManager::getController('editUserData'),
					'edit'
				])
				->name('accountManager.editUserdata');

				Route::put('update', [
					AccountManager::getController('editUserData'),
					'update'
				])
				->name('accountManager.updateUserdata');

				Route::delete('delete-media/{media}', [
					AccountManager::getController('editUserData'),
					'deleteMedia'
				])
				->name('userdatas.deleteMedia');

			});


		Route::get('logout', function(Request $request)
		{
			Auth::logout();

			$request->session()->invalidate();
			$request->session()->regenerateToken();

			return redirect('/');
		})->name('accountManager.logout');

		Route::group([
			'middleware' => ['role:superadmin|administrator'],
			],
			function()
			{
				Route::resource('users', AccountManager::getController('user'));

				Route::put('activate-users', [
					AccountManager::getController('user'),
					'activateBulk'
				])->name('users.activate');

				Route::resource('roles', AccountManager::getController('role'));
				Route::resource('permissions', AccountManager::getController('permission'));				
			});





});