<?php

namespace TransformStudios\Events\Tests;

use Statamic\Statamic;
use Statamic\Extend\Manifest;
use TransformStudios\Events\ServiceProvider;
use Statamic\Providers\StatamicServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        require_once __DIR__.'/ExceptionHandler.php';

        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            StatamicServiceProvider::class,
            ServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Statamic' => Statamic::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->make(Manifest::class)->manifest = [
            'transformstudios/events' => [
                'id' => 'transformstudios/events',
                'namespace' => 'TransformStudios\\Events\\',
            ],
        ];

        Statamic::pushActionRoutes(function () {
            return require_once realpath(__DIR__.'/../routes/actions.php');
        });
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $configs = [
            'assets', 'cp', 'forms', 'static_caching',
            'sites', 'stache', 'system', 'users',
        ];

        foreach ($configs as $config) {
            $app['config']->set("statamic.$config", require(__DIR__."/../vendor/statamic/cms/config/{$config}.php"));
        }

        $app['config']->set('statamic.users.repository', 'file');
    }
}
