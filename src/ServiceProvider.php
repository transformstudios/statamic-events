<?php

namespace TransformStudios\Events;

use Statamic\Entries\Entry;
use Statamic\Facades\Collection;
use Statamic\Facades\Field as FieldFacade;
use Statamic\Fields\Field;
use Statamic\Fields\Value;
use Statamic\Fieldtypes\Dictionary;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon()
    {
        FieldFacade::computedDefault('default-events-timezone', fn () => Statamic::displayTimezone());
        FieldFacade::computedDefault('default-event-timezone', fn () => Events::defaultTimezone());

        collect(Events::setting('collections', ['events']))
            ->each(fn (string $collection) => Collection::computed(
                $collection,
                'timezone',
                $this->timezone(...)
            ));
    }

    private function timezone(Entry $entry, $value): string|Value
    {
        $value ??= Events::defaultTimezone();

        if ($entry->blueprint()->fields()->get('timezone')?->fieldtype() instanceof Dictionary) {
            return $value;
        }

        return (new Field('timezone', ['type' => 'timezones', 'max_items' => 1]))
            ->setValue($value)
            ->setParent($entry)
            ->augment()
            ->value();
    }
}
