<?php

namespace Drhtoo\MetaField;

use Illuminate\Support\ServiceProvider;

class LaravelMetaFieldServiceProvider extends ServiceProvider
{
    /**
     * 
     * Register any application services.
     * 
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/metafield.php', 'metafield');
    }

    /**
     * 
     * Bootstrap any package services.
     * 
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/metafield.php' => config_path('metafield.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations')
        ], 'migrations');
    }
}
