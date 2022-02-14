<?php

namespace TransformStudios\Events;

use Illuminate\Support\Carbon;
use Statamic\Entries\Entry;
use Statamic\Entries\EntryCollection;
use Statamic\Facades\Entry as EntryFacade;

class Events
{
    private string $collection = 'events';
    private int $limit = 1;
    private array $taxonomies = [];

    public static function make(): self
    {
        return new static;
    }

    private function __construct()
    {
        EntryCollection::macro(
            'generate',
            fn (callable $occurrences) => $this
                ->map($occurrences)
                ->flatten()
                ->sortBy(fn (Entry $occurrence) => $occurrence->augmentedValue('start'))
        );
    }

    public function collection(string $handle): self
    {
        $this->collection = $handle;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function between(Carbon $from, Carbon $to): EntryCollection
    {
        return $this
            ->get()
            ->generate(fn (Entry $entry) => EventFactory::createFromEntry(event: $entry)->occurrencesBetween(from: $from, to: $to));
    }

    public function upcoming(int $limit = 1): EntryCollection
    {
        return $this
            ->get()
            ->generate(fn (Entry $entry) => EventFactory::createFromEntry(event: $entry)->nextOccurrences(limit: $limit));
    }

    private function get(): EntryCollection
    {
        return EntryFacade::query()
            ->where('collection', $this->collection)
            // @todo filtering by taxonomies and other filters
            ->get();
    }
}
