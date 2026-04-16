<?php

namespace CodeInstinct\ArtisanDocs\Tests;

use CodeInstinct\ArtisanDocs\ArtisanDocsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ArtisanDocsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('artisan-docs.default_format', 'markdown');
        $app['config']->set('artisan-docs.default_output', 'docs/commands.md');
        $app['config']->set('artisan-docs.include_hidden', false);
        $app['config']->set('artisan-docs.include_vendor', true);
        $app['config']->set('artisan-docs.excluded_namespaces', ['_', 'completion']);
        $app['config']->set('artisan-docs.excluded_commands', []);
        $app['config']->set('artisan-docs.groups', [
            'make' => 'Code Generators',
            'db' => 'Database',
        ]);
        $app['config']->set('artisan-docs.app_command_paths', ['App\\Console\\Commands\\']);
        $app['config']->set('artisan-docs.html_template', 'artisan-docs::commands');
        $app['config']->set('artisan-docs.title', 'Artisan Command Reference');
    }
}
