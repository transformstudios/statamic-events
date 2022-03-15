<?php

namespace TransformStudios\Events;

use Illuminate\Support\Carbon;
use Statamic\Entries\Entry;
use Statamic\Entries\EntryCollection;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Site;
use Statamic\Stache\Query\EntryQueryBuilder;
use Statamic\Support\Arr;
use Statamic\Tags\Concerns\QueriesConditions;

class Events
{
    use QueriesConditions;

    private EntryCollection $entries;
    private array $filters = [];
    private ?int $limit = null;
    private ?EntryCollection $occurrences = null;
    private array $terms = [];

    public static function fromCollection(string $handle): self
    {
        return new static(collection: $handle);
    }

    private function __construct(private string $collection)
    {
    }

    public function filter(string $fieldCondition, string $value): self
    {
        $this->filters[$fieldCondition] = $value;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function site(string $handle): self
    {
        $this->site = $handle;

        return $this;
    }

    public function terms(string|array $terms): self
    {
        $this->terms = Arr::wrap($terms);

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
        $query = EntryFacade::query()
            ->where('collection', $this->collection)
            ->where('site', $this->site ?? Site::current()->handle())
            ->where('status', 'published')
            ->when($this->terms, fn (EntryQueryBuilder $query, $terms) => $query->whereTaxonomyIn($terms));

        collect($this->filters)->each(function ($value, $fieldCondition) use ($query) {
            [$field, $condition] = explode(':', $fieldCondition);

            $this->queryCondition(query: $query, field: $field, condition: $condition, value: $value);
        });

        $this->entries = $query->get();

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
