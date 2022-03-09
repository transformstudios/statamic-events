<?php

namespace TransformStudios\Events;

use Illuminate\Support\Carbon;
use Statamic\Entries\Entry;
use Statamic\Entries\EntryCollection;
use Statamic\Facades\Entry as EntryFacade;

class Events
{
    private string $collection = 'events';
    private ?int $limit = null;
    private ?EntryCollection $occurrences = null;
    private array $taxonomies = [];

    public static function make(): self
    {
        return new static;
    }

    private function __construct()
    {
        // https://laravel.com/docs/9.x/collections#extending-collections
        // receives a collection of Entries
        EntryCollection::macro(
            'occurrences',
            fn (callable $occurrences) => $this
                // takes each entry and generates a collection of Events
                // we pass in different closures that generate different events,
                // then flatten
                ->flatMap($occurrences)
                ->sortBy(fn (Entry $occurrence) => $occurrence->augmentedValue('start'))
                ->values()
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
        return $this->output(fn (Entry $entry) => EventFactory::createFromEntry(event: $entry)->occurrencesBetween(from: $from, to: $to));
    }

    public function upcoming(int $limit = 1): EntryCollection
    {
        return $this->output(fn (Entry $entry) => EventFactory::createFromEntry(event: $entry)->nextOccurrences(limit: $limit));
    }

    private function output(callable $type): EntryCollection
    {
        return $this
            ->entries()
            ->occurrences($type)
            ->take($this->limit);
    }

    // gets the relevant entries, based on the filters etc
    private function entries(): EntryCollection
    {
        return EntryFacade::query()
            ->where('collection', $this->collection)
            // @todo filtering by taxonomies and other filters
            ->get();
    }
}
