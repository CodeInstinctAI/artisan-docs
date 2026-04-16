<?php

namespace CodeInstinct\ArtisanDocs;

use CodeInstinct\ArtisanDocs\Commands\GenerateDocsCommand;
use Illuminate\Support\ServiceProvider;

class ArtisanDocsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/artisan-docs.php',
            'artisan-docs'
        );

        $this->app->singleton(CommandInspector::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(
            __DIR__.'/../resources/views',
            'artisan-docs'
        );

        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__.'/../config/artisan-docs.php' => config_path('artisan-docs.php'),
            ], 'artisan-docs-config');

            // Publish views so developers can customise the HTML template
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/artisan-docs'),
            ], 'artisan-docs-views');

            // Register the Artisan command
            $this->commands([
                GenerateDocsCommand::class,
            ]);
        }
    }
}
