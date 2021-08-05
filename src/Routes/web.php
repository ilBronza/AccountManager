<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group([
	'middleware' => ['web', 'auth'],
	'prefix' => 'account-management',
	'namespace' => 'IlBronza\AccountManager\Http\Controllers'
	],
	function()
	{
		Route::get('edit-account', 'EditAccountController@edit')->name('accountManager.account');
		Route::put('update-account', 'EditAccountController@update')->name('accountManager.update');

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
				Route::resource('users', 'UserController');
				Route::resource('roles', 'RoleController');
				Route::resource('permissions', 'PermissionController');				
			});

	});