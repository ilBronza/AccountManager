<?php

namespace IlBronza\AccountManager;

use IlBronza\AccountManager\Http\Middleware\CheckActiveUser;
use IlBronza\AccountManager\Http\Middleware\ManageRolesAndPermissionsMiddleware;
use IlBronza\AccountManager\Models\User;
use IlBronza\AccountManager\Models\Userdata;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AccountManagerServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * 
         * 
         **/

        Relation::morphMap([
            'User' => User::getProjectClassname(),
            'Userdata' => Userdata::getProjectClassname(),
        ]);


        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'accountmanager');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'accountmanager');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        app('router')->pushMiddlewareToGroup('web', ManageRolesAndPermissionsMiddleware::class);
        app('router')->pushMiddlewareToGroup('web', CheckActiveUser::class);

        $this->publishes([
            __DIR__.'/../resources/views/auth' => resource_path('views/auth'),
        ], 'accountmanager');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/accountmanager.php', 'accountmanager');

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'accountmanager');

        // Register the service the package provides.
        $this->app->singleton('accountmanager', function ($app) {
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
            __DIR__.'/../config/accountmanager.php' => config_path('accountmanager.php'),
        ], 'accountmanager.config');

        // Publishing the views.
        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views'),
        ], 'accountmanager.views');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations')
        ], 'accountmanager.migrations');

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/ilbronza'),
        ], 'accountmanager.views');*/

        // Publishing the translation files.
        $this->publishes([
            __DIR__.'/../resources/lang' => base_path('resources/lang'),
        ], 'accountmanager.lang');

        // Registering package commands.
        // $this->commands([]);
    }
}
