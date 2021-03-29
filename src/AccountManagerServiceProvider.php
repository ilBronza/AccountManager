<?php

namespace IlBronza\AccountManager;

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
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'ilbronza');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'ilbronza');
        $this->loadMigrationsFrom(__DIR__.'/Migrations');
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/accountmanager.php', 'accountmanager');

        $this->app->make('IlBronza\AccountManager\Http\Controllers\EditAccountController');
        $this->app->make('IlBronza\AccountManager\Http\Controllers\UserController');
        $this->app->make('IlBronza\AccountManager\Http\Controllers\RoleController');
        $this->app->make('IlBronza\AccountManager\Http\Controllers\PermissionController');



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
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/ilbronza'),
        ], 'accountmanager.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/ilbronza'),
        ], 'accountmanager.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/ilbronza'),
        ], 'accountmanager.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
