<?php

namespace TransformStudios\Events;

use Carbon\CarbonInterface;
use Illuminate\Support\Traits\Conditionable;
use Statamic\Entries\Entry;
use Statamic\Entries\EntryCollection;
use Statamic\Extensions\Pagination\LengthAwarePaginator;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Site;
use Statamic\Stache\Query\EntryQueryBuilder;
use Statamic\Support\Arr;
use Statamic\Tags\Concerns\QueriesConditions;

class Events
{
    use Conditionable;
    use QueriesConditions;

    private bool $collapseMultiDays = false;

    private ?string $collection = null;

    private EntryCollection $entries;

    private ?string $event = null;

    private array $filters = [];

    private ?int $page = null;

    private ?int $perPage = null;

    private ?string $site = null;

    private array $terms = [];

    public static function fromCollection(string $handle): self
    {
        return tap(new static())->collection($handle);
    }

    public static function fromEntry(string $id): self
    {
        return tap(new static())->event($id);
    }

    private function __construct()
    {
    }

    public function collapseMultiDays(): self
    {
        $this->collapseMultiDays = true;

        return $this;
    }

    public function collection(string $handle): self
    {
        $this->collection = $handle;

        return $this;
    }

    public function event($id): self
    {
        $this->event = $id;

        return $this;
    }

    public function filter(string $fieldCondition, $value): self
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

    public function between(string|CarbonInterface $from, string|CarbonInterface $to): EntryCollection|LengthAwarePaginator
    {
        return $this->output(
            type: fn (Entry $entry) => EventFactory::createFromEntry(event: $entry, collapseMultiDays: $this->collapseMultiDays)->occurrencesBetween(from: $from, to: $to)
        );
    }

    public function upcoming(int $limit = 1): EntryCollection|LengthAwarePaginator
    {
        return $this->output(
            type: fn (Entry $entry) => EventFactory::createFromEntry(event: $entry, collapseMultiDays: $this->collapseMultiDays)->nextOccurrences(limit: $limit)
        );
    }

    private function output(callable $type): EntryCollection|LengthAwarePaginator
    {
        $occurrences = $this->entries()->occurrences(generator: $type);

        return $this->page ? $this->paginate(occurrences: $occurrences) : $occurrences;
    }

    private function entries(): self
    {
        $query = EntryFacade::query()
            ->when(
                $this->event,
                fn (EntryQueryBuilder $query, $id) => $query->where('id', $id),
                fn (EntryQueryBuilder $query) => $query->where('collection', $this->collection)
            )->where('site', $this->site ?? Site::current()->handle())
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
            ->flatMap(callback: $generator)
            ->sortBy(fn (Entry $occurrence) => $occurrence->start)
            ->values();
    }

    private function paginate(EntryCollection $occurrences): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: $occurrences->forPage(page: $this->page, perPage: $this->perPage),
            total: $occurrences->count(),
            perPage: $this->perPage,
            currentPage: $this->page,
            options: [
                'path' => request()->url(),
            ]
        );
    }
}
