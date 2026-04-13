<?php

namespace TransformStudios\Events;

use Statamic\Facades\Collection;
use Statamic\Facades\Field;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon()
    {
        Field::computedDefault('default-events-timezone', fn () => Statamic::displayTimezone());
        Field::computedDefault('default-event-timezone', fn () => Events::defaultTimezone());

        collect(Events::setting('collections', ['events']))
            ->each(function (string $collection) {
                Collection::findByHandle($collection)->entryBlueprint()->ensureField(
                    'timezone',
                    [
                        'dictionary' => 'timezones',
                        'max_items' => '1',
                        'type' => 'dictionary',
                        'default' => 'computed:default-event-timezone',
                    ]);
            });
    }
}
