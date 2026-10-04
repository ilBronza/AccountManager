<?php

namespace IlBronza\AccountManager\Tests;

use IlBronza\AccountManager\AccountManagerServiceProvider;
use App\Models\ProjectSpecific\User;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Tests\Support\SqliteConnection;
use IlBronza\Buttons\ButtonsServiceProvider;
use IlBronza\CRUD\CRUDServiceProvider;
use IlBronza\Datatables\DatatablesServiceProvider;
use IlBronza\Form\FormServiceProvider;
use IlBronza\FormField\FormFieldServiceProvider;
use IlBronza\Menu\MenuServiceProvider;
use IlBronza\UikitTemplate\UikitTemplate;
use IlBronza\UikitTemplate\UikitTemplateServiceProvider;
use IlBronza\Ukn\UknServiceProvider;
use Illuminate\Database\Connectors\SQLiteConnector;
use Laravel\Socialite\SocialiteServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends Orchestra
{
	protected function getPackageProviders($app)
	{
		return [
			PermissionServiceProvider::class,
			MediaLibraryServiceProvider::class,
			SocialiteServiceProvider::class,
			CRUDServiceProvider::class,
			AccountManagerServiceProvider::class,
			UknServiceProvider::class,
			MenuServiceProvider::class,
			UikitTemplateServiceProvider::class,
			ButtonsServiceProvider::class,
			FormFieldServiceProvider::class,
			FormServiceProvider::class,
			DatatablesServiceProvider::class,
		];
	}

	protected function defineEnvironment($app)
	{
		$app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
		$app['config']->set('app.locale', 'it');
		$app['config']->set('app.template', UikitTemplate::class);
		$app['config']->set('menu.childrenPerColumn', 8);
		$app['config']->set('menu.serviceProviders', []);
		$app['config']->set('cache.default', 'array');
		$app['config']->set('session.driver', 'array');
		$app['config']->set('activitylog.enabled', false);
		$app['config']->set('crud.broadcast.enabled', false);
		$app['config']->set('accountmanager.logUserAccess.connection', 'testbench');

		$app['config']->set('database.default', 'testbench');
		$app['config']->set('database.connections.testbench', [
			'driver' => 'sqlite',
			'database' => ':memory:',
			'prefix' => '',
		]);
		$app['db']->extend('sqlite', static function ($config, $name) {
			$config['name'] = $name;
			return new SqliteConnection(
				(new SQLiteConnector())->connect($config),
				$config['database'],
				$config['prefix'],
				$config
			);
		});

		$app['config']->set('auth.defaults.guard', 'web');
		$app['config']->set('auth.guards.web', [
			'driver' => 'session',
			'provider' => 'users',
		]);
		$app['config']->set('auth.providers.users', [
			'driver' => 'eloquent',
			'model' => User::class,
		]);

		$app['config']->set('permission.models.role', \IlBronza\AccountManager\Models\Role::class);
		$app['config']->set('permission.models.permission', \IlBronza\AccountManager\Models\Permission::class);
		$app['router']->aliasMiddleware('role', \Spatie\Permission\Middleware\RoleMiddleware::class);
	}

	protected function defineDatabaseMigrations()
	{
		// Only the fixture schema: the package's historical migrations assume
		// pre-existing application tables. Each test gets a new in-memory DB.
		$this->loadMigrationsFrom(__DIR__ . '/database/migrations');
	}

	protected function makeUserWithRole(string $roleName, string $email = 'tester@example.test'): User
	{
		$user = User::create([
			'name' => 'Test User',
			'email' => $email,
			'password' => bcrypt('password'),
			'active' => true,
		]);
		$user->assignRole(Role::findOrCreate($roleName, 'web'));

		return $user;
	}
}
