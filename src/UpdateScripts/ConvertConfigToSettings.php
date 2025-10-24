<?php

namespace TransformStudios\Events\UpdateScripts;

use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Statamic\Addons\Addon;
use Statamic\Addons\Settings;
use Statamic\Facades\Addon as AddonFacade;
use Statamic\Support\Arr;
use Statamic\UpdateScripts\UpdateScript;

class ConvertConfigToSettings extends UpdateScript
{
    private Addon $addon;

    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('6.0');
    }

    public function update()
    {
        $this->addon = AddonFacade::get('transformstudios/events');

        if (is_null($settings = $this->settingsFromConfig())) {
            return;
        }

        if (! $settings->save()) {
            $this->console()->error('Failed to save events settings. Please check your logs for details.');

            return;
        }

        $this->console()->info('Converted events config to settings.');

        $this->removeConfig();
    }

    private function removeConfig(): void
    {
        if ($this->files->exists($configPath = config_path('events.php'))) {
            $this->files->delete($configPath);
            $this->console()->info('Removed old events config file.');
        }
    }

    private function settingsFromConfig(): ?Settings
    {
        $config = Fluent::make($this->addon->config());

        if ($config->isEmpty()) {
            return null;
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

        return $this->addon
            ->settings()
            ->set(Arr::removeNullValues(compact('collections', 'timezone')));
    }
}
