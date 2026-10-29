<?php

namespace IlBronza\AccountManager;

use IlBronza\AccountManager\Http\Middleware\AccountManagerMiddlewareRolesPermissions;
use IlBronza\AccountManager\Http\Middleware\CheckActiveUser;
use IlBronza\AccountManager\Http\Middleware\LogUserAccess;
use IlBronza\AccountManager\Models\User;
use IlBronza\AccountManager\Models\Userdata;
use IlBronza\CRUD\Traits\IlBronzaPackages\IlBronzaServiceProviderPackagesTrait;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AccountManagerServiceProvider extends ServiceProvider
{
	use IlBronzaServiceProviderPackagesTrait;

	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot()
	{
		RateLimiter::for('accountmanager-ldap', function (Request $request) {
            $username = $request->input('username');
            $key = hash('sha256', strtolower(trim(is_string($username) ? $username : '')) . '|' . $request->ip());

            return [
                Limit::perMinute(30)->by('ldap-ip:' . $request->ip()),
                Limit::perMinute(5)->by('ldap-user:' . $key),
            ];
        });
		/**
		 *
		 *
		 **/

        \Illuminate\Support\Facades\Validator::extend('ip_or_cidr',
            fn ($attribute, $value) => \IlBronza\AccountManager\Helpers\IpAddressHelper::isIpOrCidr($value),
            'The :attribute must be a valid IP address or CIDR range.'
        );

		RateLimiter::for('accountmanager-heartbeat', fn (Request $request) =>
                Limit::perMinute(6)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()))
            );

		Relation::morphMap([
			'User' => User::gpc(),
			'Userdata' => Userdata::gpc(),
		]);

		$this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'accountmanager');
		$this->loadViewsFrom(__DIR__ . '/../resources/views', 'accountmanager');
		$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
		$this->loadRoutesFrom(__DIR__ . '/../routes/accountmanager.php');

		$this->app['router']->aliasMiddleware('accountmanager.active', CheckActiveUser::class);
		$this->app['router']->aliasMiddleware('accountmanager.roles', AccountManagerMiddlewareRolesPermissions::class);
		$this->app['router']->aliasMiddleware('accountmanager.logUserAccess', LogUserAccess::class);

		// Publishing is only necessary when using the CLI.
		if ($this->app->runningInConsole())
		{
			$this->commands([
                \IlBronza\AccountManager\Console\LinkLdapUser::class,
                \IlBronza\AccountManager\Console\TestLdapUser::class,
                \IlBronza\AccountManager\Console\ImportLdapUsers::class,
            ]);
            $this->publishes([
                __DIR__ . '/../config/accountmanager_ldap.php' => config_path('accountmanager_ldap.php'),
            ], 'accountmanager-ldap-config');
            // Opt-in migration: never loaded with the package's normal migrations.
            $this->publishes([
                __DIR__ . '/../database/ldap-migrations/2026_10_27_000000_create_accountmanager_ldap_identities_table.php'
                    => database_path('migrations/2026_10_27_000000_create_accountmanager_ldap_identities_table.php'),
                __DIR__ . '/../database/ldap-migrations/2026_10_28_000001_create_accountmanager_ldap_directory_users_table.php'
                    => database_path('migrations/2026_10_28_000001_create_accountmanager_ldap_directory_users_table.php'),
            ], 'accountmanager-ldap-migrations');
			$this->commands([\IlBronza\AccountManager\Console\CreateAdmin::class, \IlBronza\AccountManager\Console\PruneAccessLogs::class]);
			$this->bootForConsole();
		}

		app('router')->pushMiddlewareToGroup('web', CheckActiveUser::class);

		$this->publishes([
			__DIR__ . '/../resources/views/auth' => resource_path('views/auth'),
		], 'accountmanager');
	}

	/**
	 * Register any package services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/../config/accountmanager_ldap.php', 'accountmanager_ldap');
        $this->app->bind(\IlBronza\AccountManager\Auth\Ldap\DirectoryAuthenticator::class,
            \IlBronza\AccountManager\Auth\Ldap\LdapRecordDirectory::class);
        $this->app->bind(\IlBronza\AccountManager\Auth\Ldap\DirectoryReader::class,
            \IlBronza\AccountManager\Auth\Ldap\LdapRecordDirectory::class);
		$this->mergeConfigFrom(__DIR__ . '/../config/accountmanager.php', 'accountmanager');

		$this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'accountmanager');

		// Register the service the package provides.
		$this->app->singleton('accountmanager', function ($app)
		{
			return new AccountManager;
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['accountmanager'];
	}

	/**
	 * Console-specific booting.
	 *
	 * @return void
	 */
	protected function bootForConsole()
	{
		// Publishing the configuration file.
		$this->publishes([
			__DIR__ . '/../config/accountmanager.php' => config_path('accountmanager.php'),
		], 'accountmanager.config');

		// Publishing the views.
		$this->publishes([
			__DIR__ . '/Http/Controllers/Auth' => base_path('app/Http/Controllers/Auth'),
		], 'accountmanager.authControllers');

		$this->publishes([
			__DIR__ . '/../resources/views' => base_path('resources/views'),
		], 'accountmanager.views');

		$this->publishes([
			__DIR__ . '/../database/migrations/' => database_path('migrations')
		], 'accountmanager.migrations');

		// Publishing assets.
		/*$this->publishes([
			__DIR__.'/../resources/assets' => public_path('vendor/ilbronza'),
		], 'accountmanager.views');*/

		// Publishing the translation files.
		$this->publishes([
			__DIR__ . '/../resources/lang' => base_path('resources/lang'),
		], 'accountmanager.lang');

		// Registering package commands.
		// $this->commands([]);
	}
}
