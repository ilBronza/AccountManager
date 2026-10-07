<?php

use IlBronza\AccountManager\AccountManager;
use IlBronza\AccountManager\Http\Controllers\HeartbeatController;
use IlBronza\AccountManager\Http\Controllers\GithubLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::post(config('accountmanager.heartbeat.uri', 'account-management/heartbeat'), HeartbeatController::class)
	->middleware(['web', 'auth', 'accountmanager.active', 'throttle:accountmanager-heartbeat'])
	->name(config('accountmanager.routePrefix') . '.heartbeat');

if (class_exists(Socialite::class))
{
	Route::group(['middleware' => ['web']], function () {
		Route::get('/auth/redirect', function ()
		{
			return Socialite::driver('github')->redirect();
		})->name('login.github');

		Route::get('/auth/callback', GithubLoginController::class);
	});
}

Route::middleware('web')->get('logout', fn () => view('accountmanager::auth.logout'))
    ->name(config('accountmanager.routePrefix') . 'accountmanager.logout.confirm');

Route::middleware('web')->post('logout', function(Request $request)
{
	Auth::logout();
	$request->session()->invalidate();
	$request->session()->regenerateToken();
	return redirect('/');

})->name(config('accountmanager.routePrefix') . 'accountmanager.logout');

Route::group([
	'middleware' => ['web', 'auth', 'accountmanager.active'],
	'prefix' => 'account-management',
	'as' => config('accountmanager.routePrefix'),
	'routeTranslationPrefix' => AccountManager::getRouteTranslationPrefix()
	],
	function()
	{
		//EditAccountController
		Route::get('edit-account', [AccountManager::getController('user', 'editAccount'), 'edit'])->name('accountmanager.account');
		Route::put('update-account', [AccountManager::getController('user', 'updateAccount'), 'update'])->name('accountmanager.update');
	});


Route::group([
	'middleware' => ['web', 'auth', 'accountmanager.active', 'accountmanager.roles'],
	'prefix' => 'account-management',
	'as' => config('accountmanager.routePrefix'),
	'routeTranslationPrefix' => AccountManager::getRouteTranslationPrefix()
	],
	function()
	{
		//IlBronza\AccountManager\Http\Controllers\Account\EditAccountController
		Route::get('access-logs', [AccountManager::getController('accessLog', 'index'), 'index'])->name('accessLogs.index');

		
		Route::group([
				'prefix' => 'userdata',
			],
			function()
			{
				//EditUserDataAvatarController
				Route::get('{user}/edit-avatar', [AccountManager::getController('userdata', 'editAvatar'), 'userEdit'])->name('accountmanager.user.editAvatar');
				Route::put('{user}/update-avatar', [AccountManager::getController('userdata', 'updateAvatar'), 'userUpdate'])->name('accountmanager.user.updateAvatar');

//				IlBronza\AccountManager\Http\Controllers\Userdata\EditUserDataController
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

			Route::resource('roles', AccountManager::getController('role'))->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
			Route::resource('permissions', AccountManager::getController('permission'))->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);


		Route::group([
			'prefix' => 'users',
			'middleware' => ['accountmanager.roles'],
			],
			function()
			{
				Route::get('trashed', [AccountManager::getController('user', 'trashed'), 'index'])->name('users.trashed');

				Route::post('duplicate/{user}', [AccountManager::getController('user', 'duplicate'), 'duplicate'])
					->name('accountmanager.duplicate');

				Route::get('create-slim', [AccountManager::getController('user', 'createSlim'), 'create'])->name('users.createSlim');
				Route::post('store-slim', [AccountManager::getController('user', 'storeSlim'), 'store'])->name('users.storeSlim');

				Route::get('', [AccountManager::getController('user', 'index'), 'index'])->name('users.index');

//				IlBronza\AccountManager\Http\Controllers\Users\CreateUserController
				Route::post('', [AccountManager::getController('user', 'store'), 'store'])->name('users.store');
				Route::get('create', [AccountManager::getController('user', 'create'), 'create'])->name('users.create');
				Route::get('{user}', [AccountManager::getController('user', 'show'), 'show'])->name('users.show');
				Route::get('{user}/edit', [AccountManager::getController('user', 'edit'), 'edit'])->name('users.edit');

				//EditUserController
				Route::put('{user}', [AccountManager::getController('user', 'update'), 'update'])->name('users.update');

				//DestroyUserController
				Route::delete('restore/{user}', [AccountManager::getController('user', 'destroy'), 'restore'])->name('users.restore');
				Route::delete('{user}', [AccountManager::getController('user', 'destroy'), 'destroy'])->name('users.destroy');

			});

		Route::group([
			'prefix' => 'userdata',
			'middleware' => ['accountmanager.roles'],
			],
			function()
			{
				Route::get('{userdata}', [AccountManager::getController('userdata', 'admin'), 'show'])->name('userdatas.show');
				Route::get('{userdata}/edit', [AccountManager::getController('userdata', 'admin'), 'edit'])->name('userdatas.edit');
				Route::put('{userdata}', [AccountManager::getController('userdata', 'admin'), 'update'])->name('userdatas.update');
			});

});
