<?php

namespace TransformStudios\Events;

use Illuminate\Support\Carbon;
use Statamic\Entries\Entry;
use Statamic\Entries\EntryCollection;
use Statamic\Facades\Entry as EntryFacade;

class Events
{
    private string $collection = 'events';
    private EntryCollection $entries;
    private ?int $limit = null;
    private ?EntryCollection $occurrences = null;
    private array $taxonomies = [];

    public static function make(): self
    {
        return new static;
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
        return $this->output(type: fn (Entry $entry) => EventFactory::createFromEntry(event: $entry)->occurrencesBetween(from: $from, to: $to));
    }

    public function upcoming(int $limit = 1): EntryCollection
    {
        return $this->output(type: fn (Entry $entry) => EventFactory::createFromEntry(event: $entry)->nextOccurrences(limit: $limit));
    }

    private function output(callable $type): EntryCollection
    {
        return $this
            ->entries()
            ->occurrences($type)
            ->take($this->limit);
    }

    // gets the relevant entries, based on the filters etc
    private function entries(): self
    {
        $this->entries = EntryFacade::query()
            ->where('collection', $this->collection)
            // @todo filtering by taxonomies and other filters
            ->get();

        return $this;
    }

    private function occurrences(callable $generator): EntryCollection
    {
        return $this->entries
            // take each event and generate the occurences
            ->flatMap($generator)
            ->sortBy(fn (Entry $occurrence) => $occurrence->augmentedValue('start'))
            ->values();
    }
}
