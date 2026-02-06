<?php

namespace TransformStudios\Events;

use Statamic\Entries\Entry;
use Statamic\Facades\Collection;
use Statamic\Fields\Field;
use Statamic\Fields\Value;
use Statamic\Fieldtypes\Dictionary;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon()
    {
        collect(Events::setting('collections', [['collection' => 'events']]))
            ->each(fn (array $collection) => Collection::computed(
                $collection['collection'],
                'timezone',
                $this->timezone(...)
            ));
    }

    private function timezone(Entry $entry, $value): string|Value
    {
        $value ??= Events::timezone();

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
