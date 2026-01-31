<?php

namespace NafeesAhmed\Enapi\Laravel;

use Illuminate\Support\ServiceProvider;
use NafeesAhmed\Enapi\Laravel\LaravelApiController;

class LaravelServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->app->singleton(LaravelApiController::class, function ($app) {
            return new LaravelApiController();
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/config/enapi.php' => config_path('enapi.php'),
        ], 'enapi-config');

        // Load routes if enabled
        if (config('enapi.auto_register_routes', false)) {
            $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        }
    }
}
