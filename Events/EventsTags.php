<?php

namespace Statamic\Addons\Events;

use Carbon\Carbon;
use Statamic\API\Arr;
use Statamic\API\URL;
use Statamic\API\Request;
use Spatie\CalendarLinks\Link;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Statamic\Addons\Events\EventFactory;
use Statamic\Addons\Collection\CollectionTags;

class EventsTags extends CollectionTags
{
    /** @var Events */
    private $events;

    /** @var Collection */
    private $dates;

    private $paginationData;

    public function __construct()
    {
        parent::__construct();

        $this->dates = collect();
        $this->events = new Events();

        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);
    }

    public function upcoming()
    {
        $this->limit = $this->getInt('limit', 1);
        $this->offset = $this->getInt('offset', 0);

        $this->loadEvents($this->getBool('collapse_multi_days', false));

        if ($this->getBool('paginate')) {
            $this->paginate();
        } else {
            $this->dates = $this->events->upcoming($this->limit, $this->offset);
        }

        return $this->output();
    }

    public function calendar()
    {
        $calendar = new Calendar($this->getParam('collection', $this->getConfig('events_collection')));

        return $this->parseLoop($calendar->month($this->getParam('month'), $this->getParam('year')));
    }

    public function in()
    {
        $this->loadEvents($this->getBool('collapse_multi_days', false));

        $from = Carbon::now()->startOfDay();
        $to = Carbon::now()->modify($this->getParam('next'))->endOfDay();

        $this->loadDates($from, $to);

        return $this->parseLoop(
            array_merge(
                $this->makeEmptyDates($from, $to),
                $this->dates->toArray()
            )
        );
    }

    public function downloadLink()
    {
        $event = EventFactory::createFromArray($this->context);

        $from = $event->start();
        $to = $event->end();

        if ($event->isRecurring()) {
            $from->setDateFrom(carbon($this->getParam('date')));
            $to = $from->copy()->setTimeFromTimeString($event->endTime());
        }

        $title = Arr::get($this->context, 'title');
        $allDay = Arr::get($this->context, 'all_day', false);
        $location = Arr::get($this->context, 'location', '');

        $type = $this->getParam('type');

        $link = Link::create($title, $from, $to, $allDay)->address($location);

        return $link->$type();
    }

    public function nowOrParam()
    {
        $monthYear = request('month', Carbon::now()->englishMonth) . ' ' . request('year', Carbon::now()->year);

        $month = carbon($monthYear);

        if ($modify = $this->getParam('modify')) {
            $month->modify($modify);
        }

        return $month->format($this->getParam('format'));
    }

    protected function paginate()
    {
        $this->paginated = true;

        $page = (int) Request::get('page', 1);

        $this->offset = (($page - 1) * $this->limit) + $this->offset;

        $events = $this->events->upcoming($this->limit + 1, $this->offset);

        $paginator = new Paginator(
            $events,
            $this->limit,
            $page
        );

        $paginator->setPath(URL::makeAbsolute(URL::getCurrent()));
        $paginator->appends(Request::all());

        $this->paginationData = [
            'prev_page' => $paginator->previousPageUrl(),
            'next_page' => $paginator->nextPageUrl(),
        ];

        $this->dates = $events->slice(0, $this->limit);
    }

    protected function output()
    {
        $data = array_merge(
            $this->getEventsMetaData(),
            ['dates' => $this->dates->toArray()]
        );

        if ($this->paginated) {
            $data = array_merge($data, ['paginate' => $this->paginationData]);
        }

        return $this->parse($data);
    }

    private function loadDates($from, $to)
    {
        $this->dates = $this->events
            ->all($from, $to)
            ->groupBy(function ($event, $key) {
                return $event->start_date;
            })
            ->map(function ($days, $key) {
                return [
                    'date' => $key,
                    'dates' => $days->toArray(),
                ];
            });
    }

    private function loadEvents(bool $collapseMultiDays = false)
    {
        $this->parameters['show_future'] = true;

        $this->collect($this->get('collection'));

        $this->collection->each(
            function ($event) use ($collapseMultiDays) {
                $this->events->add(
                    EventFactory::createFromArray(
                        array_merge(
                            $event->toArray(),
                            [
                                'asSingleDay' => $collapseMultiDays,
                            ]
                        )
                    )
                );
            }
        );
    }

    private function makeEmptyDates($from, $to): array
    {
        $dates = [];
        $currentDay = $from;

        foreach (range(0, $to->diffInDays($from)) as $ignore) {
            $date = $currentDay->toDateString();
            $dates[$date] = [
                'date' => $date,
                'no_results' => true,
            ];
            $currentDay->addDay();
        }

        return $dates;
    }

    /**
     * Get any meta data that should be available in templates
     *
     * @return array
     */
    protected function getEventsMetaData()
    {
        return [
            'total_results' => $this->dates->count(),
        ];
    }
}
