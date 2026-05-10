<?php

namespace IlBronza\AccountManager\Tests;

use IlBronza\AccountManager\AccountManagerServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends Orchestra
{
	use RefreshDatabase;

	protected function getPackageProviders($app)
	{
		return [
			PermissionServiceProvider::class,
			AccountManagerServiceProvider::class,
		];
	}

	protected function defineEnvironment($app)
	{
		$app['config']->set('database.default', 'testbench');
		$app['config']->set('database.connections.testbench', [
			'driver' => 'sqlite',
			'database' => ':memory:',
			'prefix' => '',
		]);

		$app['config']->set('auth.defaults.guard', 'web');
		$app['config']->set('auth.guards.web', [
			'driver' => 'session',
			'provider' => 'users',
		]);
		$app['config']->set('auth.providers.users', [
			'driver' => 'eloquent',
			'model' => \IlBronza\AccountManager\Models\User::class,
		]);

		$app['config']->set('permission.models.role', \IlBronza\AccountManager\Models\Role::class);
		$app['config']->set('permission.models.permission', \IlBronza\AccountManager\Models\Permission::class);
	}

	protected function defineDatabaseMigrations()
	{
		$this->loadMigrationsFrom(__DIR__ . '/database/migrations');
	}
}

