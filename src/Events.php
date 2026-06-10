<?php

namespace TransformStudios\Events;

use Carbon\CarbonInterface;
use Exception;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Statamic\Entries\Entry;
use Statamic\Entries\EntryCollection;
use Statamic\Extensions\Pagination\LengthAwarePaginator;
use Statamic\Facades\Addon;
use Statamic\Facades\Cascade;
use Statamic\Fields\Values;
use Statamic\Support\Arr;
use Statamic\Tags\Parameters;
use TransformStudios\Events\Types\MultiDayEvent;

class Events
{
    private bool $collapseMultiDays = false;

    private ?string $collection = null;

    private EntryCollection $entries;

    private ?string $event = null;

    private ?int $offset = null;

    private ?int $page = null;

    private Parameters $params;

    private ?int $perPage = null;

    private ?string $site = null;

    private string $sort = 'asc';

    private ?string $timezone = null;

    public static function defaultTimezone(): string
    {
        return static::setting('timezone');
    }

    public static function fromCollection(string $handle): self
    {
        return new static(new Parameters(['collection' => $handle]));
    }

    public static function fromEntry(string $id): self
    {
        return new static(new Parameters(['event' => $id]));
    }

    public function __construct(Parameters $params)
    {
        $this
            ->params($params) // gotta be first cuz some of the later one push to it
            ->collection($params->get('collection', 'events'))
            ->collapseMultiDays($params->bool('collapse_multi_days'))
            ->event($params->get('event'))
            ->offset(offset: $params->int('offset'))
            ->pagination(page: Paginator::resolveCurrentPage(), perPage: $params->int('paginate'))
            ->sort($params->get('sort', 'asc'))
            ->timezone(timezone: $params->get('timezone', static::defaultTimezone()));
    }

    public static function setting(string $key, $default = null): mixed
    {
        return Addon::get('transformstudios/events')->settings()->get($key, $default);
    }

    public function collapseMultiDays(?bool $collapseMultiDays = true): self
    {
        $this->collapseMultiDays = $collapseMultiDays;

        return $this;
    }

    public function collection(string $handle): self
    {
        $this->collection = $handle;
        $this->params->put('collection', $handle);

        return $this;
    }

    public function event(?string $id = null): self
    {
        if (! is_null($id)) {
            $this->event = $id;
        }

        return $this;
    }

    public function filters(array $filters): self
    {
        foreach ($filters as $fieldCondition => $value) {
            $this->filter($fieldCondition, $value);
        }

        return $this;
    }

    public function filter(string $fieldCondition, $value): self
    {
        $this->params->put($fieldCondition, $value);

        return $this;
    }

    public function offset(int $offset): self
    {
        if ($offset > 0) {
            $this->offset = $offset;
        }

        return $this;
    }

    public function pagination(int $page = 1, int $perPage = 10): self
    {
        if ($perPage > 0) {
            $this->page = $page;
            $this->perPage = $perPage;
        }

        return $this;
    }

    public function params(Collection $params): self
    {
        $this->params = $params;

        return $this;
    }

    public function site(?string $handle = null): self
    {
        $this->site = $handle;

        return $this;
    }

    public function sort(string $direction): self
    {
        $this->sort = $direction;

        return $this;
    }

    public function terms(string|array $terms): self
    {
        // these will be term ids, `taxonomy-handle::term`
        // need to be added to the parameters like `[taxonomy:taxonomy-handle => term]`
        foreach (Arr::wrap($terms) as $termId) {
            [$taxonomy, $term] = explode('::', $termId);

            $this->params->put('taxonomy:'.$taxonomy, $term);
        }

        return $this;
    }

    public function timezone(string $timezone): self
    {
        $this->timezone = $timezone;

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

        if (! is_null($this->timezone)) {
            $occurrences->transform(function (Entry $occurrence) {
                $start = $occurrence->start->setTimezone($this->timezone);
                $end = $occurrence->end->setTimezone($this->timezone);

                return $occurrence
                    ->setSupplement('start', $start)
                    ->setSupplement('end', $end)
                    ->setSupplement('spanning', ! $start->isSameDay($end));
            });
        }

        if ($this->offset) {
            $occurrences = $occurrences->slice(offset: $this->offset);
        }

        return $this->page ? $this->paginate(occurrences: $occurrences) : $occurrences;
    }

    private function entries(): self
    {
        $params = $this->params->all();
        $this->entries = (new Entries(new Parameters($params)))->get();

        return $this;
    }

    private function isMultiDay(Entry $occurrence): bool
    {
        return EventFactory::getTypeClass(event: $occurrence) === MultiDayEvent::class;
    }

    private function occurrences(callable $generator): EntryCollection
    {
        return $this->entries
            ->filter(fn (Entry $event) => $this->hasStartDate($event))
            // take each event and generate the occurrences
            ->flatMap(callback: $generator)
            ->reject(fn (Entry $occurrence) => collect($occurrence->exclude_dates)
                ->filter(fn (Values $dateRow) => $dateRow->date)
                ->contains(fn (Values $dateRow) => $dateRow->date->isSameDay($occurrence->start))
            )->sortBy(callback: fn (Entry $occurrence) => $occurrence->start, descending: $this->sort === 'desc')
            ->values();
    }

    private function hasStartDate(Entry $occurrence): bool
    {
        if ($this->isMultiDay($occurrence)) {
            try {
                $days = collect($occurrence->days);

                return $days->isNotEmpty() && $days->every(fn (Values $day) => $day->date);
            } catch (Exception $e) {
                return false;
            }
        }

        return $occurrence->has('start_date');
    }

    private function paginate(EntryCollection $occurrences): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: $occurrences->forPage(page: $this->page, perPage: $this->perPage),
            total: $occurrences->count(),
            perPage: $this->perPage,
            currentPage: $this->page,
            options: [
                'path' => Cascade::get('uri'),
            ]
        );
    }
}
