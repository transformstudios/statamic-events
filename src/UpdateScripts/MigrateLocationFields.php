<?php

namespace TransformStudios\Events\UpdateScripts;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Entries\Entry;
use Statamic\Facades\Entry as Entries;
use Statamic\UpdateScripts\UpdateScript;
use TransformStudios\Events\Events;

class MigrateLocationFields extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('7.0');
    }

    public function update()
    {
        $skipped = collect(Events::setting('collections', ['events']))
            ->flatMap(fn (string $collection) => Entries::query()->where('collection', $collection)->get())
            ->map(fn (Entry $entry) => $this->processEntry($entry))
            ->filter();

        if ($skipped->isNotEmpty()) {
            $this->console()->warn('Skipped entries (resolve by hand):');
            $skipped->each(fn (string $line) => $this->console()->line("  - {$line}"));
        }

        $this->console()->info('Migrated event location fields to the 7.0 shape.');
    }

    private function arrayValue(array $data, string $key): ?array
    {
        return is_array($value = Arr::get($data, $key)) ? $value : null;
    }

    private function filledString(array $data, string $key): bool
    {
        return is_string($value = Arr::get($data, $key)) && filled($value);
    }

    private function migrateEntry(Entry $entry): void
    {
        $data = $entry->data()->all();
        $location = Arr::get($data, 'location');

        // Prime/foreign group: leave location alone; move link → online_url if needed.
        if (is_array($location)) {
            if (is_string($link = Arr::get($data, 'link')) && filled($link) && ! $this->filledString($data, 'online_url')) {
                $entry->set('online_url', $link)->remove('link')->save();
            }

            return;
        }

        $name = $this->resolveName($data);
        $coordinates = $this->arrayValue($data, 'coordinates');

        if (! is_null($name) || ! is_null($coordinates)) {
            $group = array_filter(compact('name', 'coordinates'), fn ($value) => ! is_null($value));
            $entry->set('location', $group);
        } elseif (is_string($location)) {
            $entry->remove('location');
        }

        if (! is_null($onlineUrl = $this->resolveOnlineUrl($data))) {
            $entry->set('online_url', $onlineUrl);
        }

        foreach (['address', 'link', 'coordinates'] as $handle) {
            if (array_key_exists($handle, $data)) {
                $entry->remove($handle);
            }
        }

        $entry->save();
    }

    private function processEntry(Entry $entry): ?string
    {
        if ($reason = $this->skipReason($entry->data()->all())) {
            return "{$entry->id()} ({$reason})";
        }

        $this->migrateEntry($entry);

        return null;
    }

    private function resolveName(array $data): ?string
    {
        if ($this->filledString($data, 'address')) {
            return $data['address'];
        }

        if ($this->filledString($data, 'location') && ! Str::isUrl($data['location'])) {
            return $data['location'];
        }

        return null;
    }

    private function resolveOnlineUrl(array $data): ?string
    {
        if ($this->filledString($data, 'online_url')) {
            return $data['online_url'];
        }

        if ($this->filledString($data, 'link')) {
            return $data['link'];
        }

        if ($this->filledString($data, 'location') && Str::isUrl($data['location'])) {
            return $data['location'];
        }

        return null;
    }

    private function skipReason(array $data): ?string
    {
        $hasAddress = $this->filledString($data, 'address');
        $hasLink = $this->filledString($data, 'link');
        $hasOnlineUrl = $this->filledString($data, 'online_url');
        $isStringLocation = $this->filledString($data, 'location');
        $isUrlLocation = $isStringLocation && Str::isUrl($data['location']);
        $isNonUrlStringLocation = $isStringLocation && ! Str::isUrl($data['location']);

        if ($hasAddress && $isNonUrlStringLocation) {
            return 'address and non-URL location both set';
        }

        if ($hasLink && $isUrlLocation) {
            return 'link and URL-valued location both set';
        }

        if ($hasOnlineUrl && ($hasLink || $isUrlLocation)) {
            return 'online_url conflicts with link or URL-valued location';
        }

        return null;
    }
}
