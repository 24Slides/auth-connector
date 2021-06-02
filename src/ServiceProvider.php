<?php

namespace Slides\Connector\Auth;

use Slides\Connector\Auth\Clients\Mandrill\Mailer;

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
        $this->loadConsoleCommands();
        $this->loadGuards();
        $this->loadRoutes();
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
        $this->publishes([__DIR__ . '/../database/migrations/' => database_path('migrations')], 'migrations');
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
            \Slides\Connector\Auth\Commands\SyncUsers::class,
            \Slides\Connector\Auth\Commands\SyncExport::class,
            \Slides\Connector\Auth\Commands\SyncImport::class,
            \Slides\Connector\Auth\Commands\ManageUsers::class,
            \Slides\Connector\Auth\Clients\Mandrill\Commands\Send::class
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
     * Load routes.
     *
     * @return void
     */
    protected function loadRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');

        \Illuminate\Support\Facades\Route::getRoutes()->refreshNameLookups();
    }

    /**
     * Register package facades
     */
    protected function registerFacades()
    {
        $this->app->singleton(Client::class, function () {
            return new Client();
        });

        $this->app->singleton(AuthService::class);

        $this->app->alias(AuthService::class,'authService');
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
                'authService' => $app['authService'],
                'client' => $app[Client::class]
            ]);
        });

        // Register the fallback driver if service is disabled
        if(!$this->app->runningInConsole() && !$this->enabled()) {
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