<?php

namespace TransformStudios\Events\UpdateScripts;

use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Statamic\Facades\Addon;
use Statamic\Support\Arr;
use Statamic\UpdateScripts\UpdateScript;

class ConvertConfigToSettings extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('6.0');
    }

    public function update()
    {
        $config = Fluent::make(config('events'));

        if ($config->isEmpty()) {
            return;
        }

        $collections = collect([$config->collection => null])
            ->merge($config->collections)
            ->map(function (array|string $collection, $handle) {
                if (is_string($collection)) {
                    return [
                        'id' => Str::random(8),
                        'collection' => $collection,
                    ];
                }

                $collectionSetting = [
                    'id' => Str::random(8),
                    'collection' => $handle,
                    'location_field' => Arr::get($collection, 'location_field', 'location'),
                ];

                return Arr::removeNullValues($collectionSetting);
            })->reject(fn (array $collection) => $collection['collection'] == 'events' && is_null(Arr::get($collection, 'location_field')))
            ->all();

        $timezone = $config->timezone;

        Addon::get('transformstudios/events')
            ->settings()
            ->set(Arr::removeNullValues(compact('collections', 'timezone')))
            ->save();
    }
}
