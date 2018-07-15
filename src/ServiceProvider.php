<?php

namespace Slides\Connector\Auth;

use Illuminate\Support\Facades\Auth;

/**
 * Class ServiceProvider
 *
 * @package Slides\Connector\Auth
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadPublishes();
        $this->loadMigrations();
        $this->loadConsoleCommands();
        $this->loadViews();
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/connector.php', 'connector'
        );

        $this->registerFacades();
        $this->registerGuard();
    }

    /**
     * Load publishes
     */
    protected function loadPublishes()
    {
        $this->publishes([__DIR__ . '/../config/connector.php' => config_path('connector.php')], 'config');
    }

    /**
     * Load migrations
     */
    protected function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Load console commands
     */
    protected function loadConsoleCommands()
    {
        if(!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            \Slides\Connector\Auth\Commands\SyncUsers::class
        ]);
    }

    /**
     * Load views
     */
    protected function loadViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'plugin');
    }

    /**
     * Register package facades
     */
    protected function registerFacades()
    {
        $this->app->singleton(AuthService::class, function($app) {
            return new AuthService();
        });

        $this->app->bind('authService', function($app) {
            return $app[AuthService::class];
        });
    }

    /**
     * Register the guard
     */
    protected function registerGuard()
    {
        Auth::extend('authServiceToken', function(\Illuminate\Foundation\Application $app) {
            return $app->make(TokenGuard::class, [
                'provider' => $app['auth']->createUserProvider($app['config']['auth.guards.web.provider']),
                'request' => $app['request']
            ]);
        });
    }
}