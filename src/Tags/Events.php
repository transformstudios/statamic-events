<?php

namespace TransformStudios\Events\Tags;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\CalendarLinks\Link;
use Statamic\Entries\EntryCollection;
use Statamic\Extensions\Pagination\LengthAwarePaginator;
use Statamic\Facades\URL;
use Statamic\Support\Arr;
use Statamic\Tags\Concerns\OutputsItems;
use Statamic\Tags\Tags;
use TransformStudios\Events\Calendar;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events as Generator;

class Events extends Tags
{
    use OutputsItems;

    public function between()
    {
        $generator = Generator::fromCollection(handle: $this->params->get('collection'));

        if ($this->params->bool('paginate')) {
            $generator->pagination(perPage: $this->params->int('per_page'));
        }

        return $this->output($generator->between(
            Carbon::parse($this->params->get('from', now()))->startOfDay(),
            Carbon::parse($this->params->get('to'))->endOfDay()
        ));
    }

    public function calendar(): array
    {
        $calendar = new Calendar($this->params->get('collection', config('events.events_collection')));

        return array_values($calendar->month($this->params->get('month'), $this->params->get('year')));
    }

    public function downloadLink(): string
    {
        $event = EventFactory::createFromArray($this->context);

        $from = $event->start();
        $to = $event->end();

        if ($event->isRecurring()) {
            $from->setDateFrom(Carbon::parse($this->params->get('date')));
            $to = $from->copy()->setTimeFromTimeString($event->endTime());
        }

        $title = Arr::get($this->context, 'title');
        $location = Arr::get($this->context, 'location', '');

        $type = $this->params->get('type', 'ics');

        $link = Link::create($title, $from, $to, $event->isAllDay())->address($location);

        return $link->$type();
    }

    public function in()
    {
        $generator = Generator::fromCollection(handle: $this->params->get('collection'));

        if ($this->params->bool('paginate')) {
            $generator->pagination(perPage: $this->params->int('per_page'));
        }

        return $this->output($generator->between(
            Carbon::now()->startOfDay(),
            Carbon::now()->modify($this->params->get('next'))->endOfDay()
        ));

        // $this->loadEvents($this->params->bool('collapse_multi_days', false));
    }

    public function nowOrParam(): string
    {
        $monthYear = request('month', Carbon::now()->englishMonth).' '.request('year', Carbon::now()->year);

        $month = Carbon::parse($monthYear);

        if ($modify = $this->params->get('modify')) {
            $month->modify($modify);
        }

        return $month->format($this->params->get('format'));
    }

    public function today(): array
    {
        $this->loadEvents($this->params->bool('collapse_multi_days', false));

        $this->loadDates(
            $this->params->get('ignore_finished') ? Carbon::now() : Carbon::now()->startOfDay(),
            Carbon::now()->endOfDay(),
            false
        );

        return $this->dates->toArray();
    }

    public function upcoming(): EntryCollection|array
    {
        $generator = Generator::fromCollection(handle: $this->params->get('collection'));

        if ($this->params->bool('paginate')) {
            $generator->pagination(perPage: $this->params->int('per_page'));
        }

        return $this->output($generator->upcoming($this->params->int('limit')));
    }

    private function setOffsetForPagination(): void
    {
        $page = (int) request('page', 1);

        $this->offset = (($page - 1) * $this->limit) + $this->offset;
    }

    protected function paginate(Collection $events): void
    {
        $this->paginated = true;

        $page = (int) request('page', 1);

        $paginator = new LengthAwarePaginator(
            items: $events,
            total: $count = $events->count(),
            perPage: $this->limit,
            currentPage: $page
        );

        $paginator
            ->setPath(URL::makeAbsolute(URL::getCurrent()))
            ->appends(request()->all());

        $this->paginationData = [
            'total_items'    => $count,
            'items_per_page' => $this->limit,
            'total_pages'    => $paginator->lastPage(),
            'current_page'   => $paginator->currentPage(),
            'prev_page'      => $paginator->previousPageUrl(),
            'next_page'      => $paginator->nextPageUrl(),
            'auto_links'     => $paginator->render(),
            'links'          => $paginator->render(),
        ];

        $this->dates = $events->slice(0, $this->limit);
    }

    protected function paginateBetween(Collection $events): void
    {
        $this->paginated = true;

        $page = (int) request('page', 1);

        $paginator = new LengthAwarePaginator(
            items: $events,
            total: $count = $events->count(),
            perPage: $this->limit,
            currentPage: $page
        );

        $paginator
            ->setPath(URL::makeAbsolute(URL::getCurrent()))
            ->appends(request()->all());

        $this->paginationData = [
            'total_items'    => $count,
            'items_per_page' => $this->limit,
            'total_pages'    => $paginator->lastPage(),
            'current_page'   => $paginator->currentPage(),
            'prev_page'      => $paginator->previousPageUrl(),
            'next_page'      => $paginator->nextPageUrl(),
            'auto_links'     => $paginator->render(),
            'links'          => $paginator->render(),
        ];

        $this->dates = $events->slice($this->offset, $this->limit);
    }

    protected function outputData(): array
    {
        $data = array_merge(
            $this->getEventsMetaData(),
            ['dates' => $this->dates->toArray()]
        );

        if ($this->paginated) {
            $data = array_merge($data, ['paginate' => $this->paginationData]);
        }

        return $data;
    }

    private function loadDates(Carbon|string $from, Carbon|string $to, bool $groupByDate = true): void
    {
        $this->dates = $this->events
            ->all($from, $to)
            ->when($groupByDate, function (Collection $events) {
                return $events
                    ->groupBy(fn ($event, $key) => $event->start_date)
                    ->map(fn ($days, $key) => [
                            'date' => $key,
                            'dates' => $days->toArray(),
                        ]);
            });
    }

    private function loadEvents(bool $collapseMultiDays = false)
    {
        $this->params->put('show_future', true);
        if (! $this->params->has('collection')) {
            $this->params->put('from', 'events');
        }

        // Need to "remove" the limit & paginate, otherwise the `collect` below will limit & paginate the entries.
        // We need to get all the entries, then make the events AND THEN limit & paginate.
        // Didn't use a new parameter because that would break all existing instances and
        // would be a much larger code change.
        // @TODO refactor when move to v3
        $limit = $this->params->pull('limit');

        $paginate = $this->params->pull('paginate', false);

        $events = parent::index();

        if ($limit) {
            $this->limit = $limit;
            $this->params->put('limit', $limit);
        }

        if ($paginate) {
            $this->params->put('paginate', $paginate);
        }

        if ($as = $this->params->get('as')) {
            $events = $events[$as];
        }

        $events->each(
            fn ($event) => $this->events->add(
                EventFactory::createFromArray(
                    $event
                        ->merge(
                            [
                                'asSingleDay' => $collapseMultiDays,
                                'has_end_time' => $event->has('end_time'),
                            ]
                        )->toAugmentedArray(),
                )
            )
        );
    }

    private function makeEmptyDates(Carbon| string $from, Carbon|string $to): array
    {
        $dates = [];
        $currentDay = $from = Carbon::parse($from);

        foreach (range(0, Carbon::parse($to)->diffInDays($from)) as $ignore) {
            $date = $currentDay->toDateString();
            $dates[$date] = [
                'date' => $date,
                'no_results' => true,
            ];
            $currentDay->addDay();
        }

        return $dates;
    }

    protected function getEventsMetaData(): array
    {
        return [
            'no_results' => $this->dates->isEmpty(),
            'total_results' => $this->dates->count(),
        ];
    }
}
