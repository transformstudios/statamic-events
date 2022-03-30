<?php

namespace TransformStudios\Events;

use Carbon\CarbonInterface;
use Illuminate\Support\Traits\Conditionable;
use Statamic\Entries\Entry;
use Statamic\Entries\EntryCollection;
use Statamic\Extensions\Pagination\LengthAwarePaginator;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Stache\Query\EntryQueryBuilder;
use Statamic\Support\Arr;
use Statamic\Tags\Concerns\QueriesConditions;

class Events
{
    use Conditionable, QueriesConditions;

    private EntryCollection $entries;
    private array $filters = [];
    private ?int $page = null;
    private ?int $perPage = null;
    private ?string $site = null;
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

    public function pagination(int $page = 1, int $perPage = 10): self
    {
        $this->page = $page;
        $this->perPage = $perPage;

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

    public function between(CarbonInterface $from, CarbonInterface $to): EntryCollection|LengthAwarePaginator
    {
        return $this->output(
            type: fn (Entry $entry) => EventFactory::createFromEntry(event: $entry)->occurrencesBetween(from: $from, to: $to)
        );
    }

    public function upcoming(int $limit = 1): EntryCollection|LengthAwarePaginator
    {
        return $this->output(
            type: fn (Entry $entry) => EventFactory::createFromEntry(event: $entry)->nextOccurrences(limit: $limit)
        );
    }

    private function output(callable $type): EntryCollection|LengthAwarePaginator
    {
        $occurrences = $this->entries()->occurrences($type);

        return $this->page ? $this->paginate($occurrences) : $occurrences;
    }

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
            ->sortBy(fn (Entry $occurrence) => $occurrence->start)
            ->values();
    }

    private function paginate(EntryCollection $occurrences): LengthAwarePaginator
    {
        /*
                    fn (LengthAwarePaginator $paginator) => $paginator
                ->setPath(URL::makeAbsolute(URL::getCurrent()))
                ->appends(request()->all())
        */
        return new LengthAwarePaginator(
            $occurrences->forPage($this->page, $this->perPage),
            $occurrences->count(),
            $this->perPage,
            $this->page
        );
    }
}
