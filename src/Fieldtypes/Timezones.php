<?php

namespace TransformStudios\Events\Fieldtypes;

use Statamic\Fieldtypes\Relationship;
use Statamic\Support\Arr;

class Timezones extends Relationship
{
    protected static $handle = 'timezones';

    protected function augmentValue($key)
    {
        if (is_null($augmented = $this->timezone($key))) {
            return [
                'abbreviation' => $key,
                'timezone' => $key,
            ];
        }

        return $augmented;
    }

    public function getIndexItems($request)
    {
        return collect($this->timezones())
            ->map(fn (array $zone) => $this->toItemArray($zone['timezone']));
    }

    public function preProcess($data)
    {
        if (is_array($data)) {
            return [Arr::get($data, 'timezone')];
        }

        return Arr::wrap($data);
    }

    protected function toItemArray($key)
    {
        if (is_null($key)) {
            return [];
        }

        if (is_null($zone = $this->timezone($key))) {
            return [];
        }

        return [
            'id' => $key,
            'title' => $key.' ('.$zone['abbreviation'].')',
        ];
    }

    private function timezone(string $key): ?array
    {
        return collect($this->timezones())->firstWhere('timezone', $key);
    }

    /**
     * @return array
     */
    private function timezones()
    {
        return cache()->rememberForever(
            'timezones',
            fn () => json_decode(file_get_contents(__DIR__.'/../../resources/timezones.json'), true)
        );
    }
}
