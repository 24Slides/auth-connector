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
        $this->loadGuards();
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
        $this->registerGuards();
    }

    /**
     * Load configs
     *
     * @return void
     */
    protected function loadPublishes()
    {
        $this->publishes([__DIR__ . '/../config/connector.php' => config_path('connector.php')], 'config');
    }

    /**
     * Load migrations
     *
     * @return void
     */
    protected function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Load console commands
     *
     * @return void
     */
    protected function loadConsoleCommands()
    {
        if(!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            \Slides\Connector\Auth\Commands\MakeAuthHandlers::class,
            \Slides\Connector\Auth\Commands\SyncUsers::class
        ]);
    }

    /**
     * Load default and fallback authentication guards.
     *
     * @return void
     */
    protected function loadGuards()
    {
        // Skip loading guards if an application running in the console
        if($this->app->runningInConsole()) {
            return;
        }

        $this->app['authService']->setGuard(
            $this->app['auth']->guard('authService')
        );

        if(!$this->enabled()) {
            $this->app['authService']->setFallbackGuard(
                $this->app['auth']->guard('fallback')
            );
        }
    }

    /**
     * Register package facades
     */
    protected function registerFacades()
    {
        $this->app->singleton(AuthService::class, function($app) {
            return new AuthService(new Client());
        });

        $this->app->bind('authService', function($app) {
            return $app[AuthService::class];
        });
    }

    /**
     * Register the guard
     *
     * @return void
     */
    protected function registerGuards()
    {
        $this->app['auth']->extend('authServiceToken', function(\Illuminate\Foundation\Application $app) {
            return $app->make(TokenGuard::class, [
                'provider' => $app['auth']->createUserProvider($app['config']['auth.guards.authService.provider']),
                'request' => $app['request'],
                'authService' => $app['authService']
            ]);
        });

        // Register the fallback driver if service is disabled
        if(!$this->enabled()) {
            $this->app['auth']->shouldUse('fallback');
        }
    }

    /**
     * Checks whether service is enabled
     *
     * @return bool
     */
    private function enabled(): bool
    {
        return config('connector.auth.enabled') === true;
    }
}