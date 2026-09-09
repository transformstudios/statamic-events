<?php

namespace TransformStudios\Events\UpdateScripts;

use Illuminate\Support\Str;
use Statamic\Facades\Entry;
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
            ->flatMap(fn (string $collection) => Entry::query()->where('collection', $collection)->get())
            ->map(fn ($entry) => $this->processEntry($entry))
            ->filter();

        if ($skipped->isNotEmpty()) {
            $this->console()->warn('Skipped entries (resolve by hand):');
            $skipped->each(fn (string $line) => $this->console()->line("  - {$line}"));
        }

        $this->console()->info('Migrated event location fields to the 7.0 shape.');
    }

    private function filledString(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }

    private function migrateEntry($entry): void
    {
        $data = $entry->data()->all();
        $location = $data['location'] ?? null;

        // Foreign/Prime group: never reshape location; only lift a lone link.
        if (is_array($location)) {
            $link = $data['link'] ?? null;

            if (is_string($link) && $link !== '' && ! $this->filledString($data['online_url'] ?? null)) {
                $entry->set('online_url', $link)->remove('link')->save();
            }

            return;
        }

        $name = $this->resolveName($data);
        $coordinates = is_array($data['coordinates'] ?? null) ? $data['coordinates'] : null;
        $onlineUrl = $this->resolveOnlineUrl($data);

        if ($name !== null || $coordinates !== null) {
            $entry->set('location', array_filter([
                'name' => $name,
                'coordinates' => $coordinates,
            ], fn ($value) => $value !== null));
        } elseif (is_string($location)) {
            $entry->remove('location');
        }

        if ($onlineUrl !== null) {
            $entry->set('online_url', $onlineUrl);
        }

        foreach (['address', 'link', 'coordinates'] as $handle) {
            if (array_key_exists($handle, $data)) {
                $entry->remove($handle);
            }
        }

        $entry->save();
    }

    private function processEntry($entry): ?string
    {
        if ($reason = $this->skipReason($entry->data()->all())) {
            return "{$entry->id()} ({$reason})";
        }

        $this->migrateEntry($entry);

        return null;
    }

    private function resolveName(array $data): ?string
    {
        if ($this->filledString($data['address'] ?? null)) {
            return $data['address'];
        }

        $location = $data['location'] ?? null;

        if ($this->filledString($location) && ! Str::isUrl($location)) {
            return $location;
        }

        return null;
    }

    private function resolveOnlineUrl(array $data): ?string
    {
        if ($this->filledString($data['online_url'] ?? null)) {
            return $data['online_url'];
        }

        if ($this->filledString($data['link'] ?? null)) {
            return $data['link'];
        }

        $location = $data['location'] ?? null;

        if ($this->filledString($location) && Str::isUrl($location)) {
            return $location;
        }

        return null;
    }

    private function skipReason(array $data): ?string
    {
        $location = $data['location'] ?? null;
        $hasAddress = $this->filledString($data['address'] ?? null);
        $hasLink = $this->filledString($data['link'] ?? null);
        $hasOnlineUrl = $this->filledString($data['online_url'] ?? null);
        $isStringLocation = $this->filledString($location);
        $isUrlLocation = $isStringLocation && Str::isUrl($location);
        $isNonUrlStringLocation = $isStringLocation && ! Str::isUrl($location);

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
