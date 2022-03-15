<?php

namespace TransformStudios\Events\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Statamic\Entries\Collection;
use Statamic\Extend\Manifest;
use Statamic\Facades\Blueprint as BlueprintFacade;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Facades\YAML;
use Statamic\Fields\Blueprint;
use Statamic\Providers\StatamicServiceProvider;
use Statamic\Statamic;
use TransformStudios\Events\ServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use PreventSavingStacheItemsToDisk;

    protected Collection $collection;
    protected Blueprint $blueprint;

    public function setup(): void
    {
        parent::setup();
        $this->preventSavingStacheItemsToDisk();
    }

    public function tearDown(): void
    {
        $this->deleteFakeStacheDirectory();

        parent::tearDown();
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
                'namespace' => 'TransformStudios\\Events',
            ],
        ];

        Statamic::pushActionRoutes(function () {
            return require_once realpath(__DIR__.'/../routes/actions.php');
        });
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $configs = ['assets', 'cp', 'forms', 'routes', 'static_caching', 'sites', 'stache', 'system', 'users'];

        foreach ($configs as $config) {
            $app['config']->set("statamic.$config", require __DIR__."/../vendor/statamic/cms/config/{$config}.php");
        }

        // Setting the user repository to the default flat file system
        $app['config']->set('statamic.users.repository', 'file');

        // Assume the pro edition within tests
        $app['config']->set('statamic.editions.pro', true);

        Statamic::booted(function () {
            $taxonomy = Taxonomy::make('categories')->save();
            Term::make('one')->taxonomy('categories')->dataForLocale('default', [])->save();
            Term::make('two')->taxonomy('categories')->dataForLocale('default', [])->save();
            $blueprintContents = YAML::parse(file_get_contents(__DIR__.'/__fixtures__/blueprints/event.yaml'));
            $blueprintFields = collect($blueprintContents['sections']['main']['fields'])
                ->keyBy(fn ($item) =>  $item['handle'])
                ->map(fn ($item) => $item['field'])
                ->all();

            $this->blueprint = BlueprintFacade::makeFromFields($blueprintFields)
                ->setNamespace('collections.events')
                ->setHandle('event')
                ->save();

            $this->collection = CollectionFacade::make('events')
                ->taxonomies(['categories'])
                ->save();
        });
    }
}
