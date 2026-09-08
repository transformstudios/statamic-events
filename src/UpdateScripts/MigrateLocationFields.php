<?php

namespace TransformStudios\Events\UpdateScripts;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Entries\Entry;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\UpdateScripts\UpdateScript;
use TransformStudios\Events\Events;

class MigrateLocationFields extends UpdateScript
{
    private Collection $skippedArrayLocation;

    private Collection $skippedAddressConflict;

    private Collection $skippedLinkConflict;

    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('7.0');
    }

    public function update()
    {
        $this->skippedArrayLocation = collect();
        $this->skippedAddressConflict = collect();
        $this->skippedLinkConflict = collect();

        $collections = collect(Events::setting('collections', ['events']))->filter()->values();

        EntryFacade::query()
            ->whereIn('collection', $collections->all())
            ->get()
            ->each(fn (Entry $entry) => $this->migrate($entry));

        $this->reportSkips();
    }

    private function migrate(Entry $entry): void
    {
        $address = $entry->get('address');
        $link = $entry->get('link');
        $location = $entry->get('location');

        if (! is_null($location) && ! is_string($location)) {
            $this->skippedArrayLocation->push($entry->id());

            return;
        }

        $locationIsUrl = is_string($location) && $location !== '' && Str::isUrl($location);
        $hasNonUrlLocation = is_string($location) && $location !== '' && ! $locationIsUrl;
        $hasAddress = is_string($address) && $address !== '';
        $hasLink = is_string($link) && $link !== '';

        if ($hasAddress && $hasNonUrlLocation) {
            $this->skippedAddressConflict->push($entry->id());

            return;
        }

        if ($hasLink && $locationIsUrl) {
            $this->skippedLinkConflict->push($entry->id());

            return;
        }

        $dirty = false;

        if ($hasAddress) {
            $entry->set('location', $address);
            $entry->remove('address');
            $dirty = true;

            if ($locationIsUrl) {
                $entry->set('online_url', $location);
            }
        } elseif ($locationIsUrl) {
            $entry->set('online_url', $location);
            $entry->remove('location');
            $dirty = true;
        }

        if ($hasLink) {
            $entry->set('online_url', $link);
            $entry->remove('link');
            $dirty = true;
        }

        if ($entry->has('address')) {
            $entry->remove('address');
            $dirty = true;
        }

        if ($entry->has('link')) {
            $entry->remove('link');
            $dirty = true;
        }

        if ($dirty) {
            $entry->save();
        }
    }

    private function reportSkips(): void
    {
        if ($this->skippedArrayLocation->isNotEmpty()) {
            $this->console()->warn(
                'Skipped entries with non-string location (owned by another package): '
                .$this->skippedArrayLocation->unique()->implode(', ')
            );
        }

        if ($this->skippedAddressConflict->isNotEmpty()) {
            $this->console()->warn(
                'Skipped entries with both address and a non-URL location (resolve manually): '
                .$this->skippedAddressConflict->unique()->implode(', ')
            );
        }

        if ($this->skippedLinkConflict->isNotEmpty()) {
            $this->console()->warn(
                'Skipped entries with both link and a URL-valued location (resolve manually): '
                .$this->skippedLinkConflict->unique()->implode(', ')
            );
        }

        $this->console()->info('Migrated event location fields to location / online_url.');
    }
}
