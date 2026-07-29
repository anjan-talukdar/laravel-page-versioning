<?php

namespace AnjanTalukdar\PageVersioning;

use AnjanTalukdar\PageVersioning\Console\Commands\InstallCommand;
use AnjanTalukdar\PageVersioning\Console\Commands\InstallFilamentCommand;
use AnjanTalukdar\PageVersioning\Services\PageService;
use Illuminate\Support\ServiceProvider;

class PageVersioningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/page-versioning.php',
            'page-versioning'
        );

        $this->app->singleton(PageService::class, function ($app) {
            return new PageService();
        });
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'page-versioning');

        // Load routes if enabled in config
        if (config('page-versioning.register_routes', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        // Console commands and publishables
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                InstallFilamentCommand::class,
            ]);

            // Config publishing
            $this->publishes([
                __DIR__ . '/../config/page-versioning.php' => config_path('page-versioning.php'),
            ], 'page-versioning-config');

            // Migration publishing
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'page-versioning-migrations');

            // Views publishing
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/page-versioning'),
            ], 'page-versioning-views');

            // Filament resources publishing for full customization
            $this->publishes([
                __DIR__ . '/Filament/Resources' => app_path('Filament/Resources/PageVersioning'),
            ], 'page-versioning-filament');
        }
    }
}
