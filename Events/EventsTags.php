<?php

namespace Statamic\Addons\Events;

use Carbon\Carbon;
use Statamic\API\URL;
use Statamic\API\Entry;
use Statamic\API\Request;
use Statamic\Extend\Tags;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;

class EventsTags extends Tags
{
    /** @var Events */
    private $events;

    /** @var Collection */
    private $dates;

    private $offset;

    private $limit;

    private $paginated;

    private $paginationData;

    public function __construct()
    {
        parent::__construct();

        $this->dates = collect();
        $this->events = new Events();

        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);
    }

    public function next()
    {
        $this->limit = $this->getInt('limit', 1);
        $this->offset = $this->getInt('offset', 0);

        Entry::whereCollection($this->get('collection'))
            ->each(function ($event) {
                $this->events->add($event->toArray());
            });

        if ($this->getBool('paginate')) {
            $this->paginate();
        } else {
            $this->dates = $this->events->next($this->limit, $this->offset);
        }

        return $this->output();
    }

    public function calendar()
    {
        Entry::whereCollection($this->getParam('collection'))
            ->each(function ($event) {
                $this->events->add($event->toArray());
            });

        $month = carbon($this->getParam('month', Carbon::now()))
            ->year($this->getParam('year', Carbon::now()->year));

        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $this->dates = $this->events
            ->all($from, $to)
            ->groupBy(function ($event, $key) {
                return carbon($event['date'])->toDateString();
            })
            ->map(function ($days, $key) {
                return [
                    'date' => $key,
                    'dates' => $days->toArray(),
                ];
            });

        $currentDay = $from->copy()->startOfWeek();
        $lastDay = $to->copy()->endOfWeek();
        $daysToRender = $lastDay->diffInDays($currentDay);

        $dates = [];

        for ($i = 0; $i <= $daysToRender; $i++) {
            $date = $currentDay->copy()->toDateString();
            $dates[$date] = [
                'date' => $date,
                'no_results' => true,
            ];
            $currentDay->addDay();
        }

        return $this->parseLoop(array_merge($dates, $this->dates->toArray()));
    }

    private function paginate()
    {
        $this->paginated = true;

        $page = (int) Request::get('page', 1);

        $this->offset = (($page - 1) * $this->limit) + $this->offset;

        $events = $this->events->next($this->limit + 1, $this->offset);

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

    private function output()
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
