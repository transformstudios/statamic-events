<?php

namespace TransformStudios\Events\Tests;

use Statamic\Entries\Collection;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Fieldset;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Fields\Blueprint;
use Statamic\Fields\BlueprintRepository;
use Statamic\Statamic;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use TransformStudios\Events\ServiceProvider;

abstract class TestCase extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected Collection $collection;

    protected Blueprint $blueprint;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Assume the pro edition within tests
        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('events.timezone', 'UTC');

        Statamic::booted(function () {
            Fieldset::addNamespace('events', __DIR__.'/../resources/fieldsets');
            app()->extend(BlueprintRepository::class, fn ($repo) => $repo->setDirectory(__DIR__.'/__fixtures__/blueprints'));

            Taxonomy::make('categories')->save();
            Term::make('one')->taxonomy('categories')->dataForLocale('default', [])->save();
            Term::make('two')->taxonomy('categories')->dataForLocale('default', [])->save();

            $this->collection = CollectionFacade::make('events')
                ->taxonomies(['categories'])
                ->save();
        });
    }
}
