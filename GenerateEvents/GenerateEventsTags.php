<?php

namespace Statamic\Addons\GenerateEvents;

use Carbon\Carbon;
use Statamic\API\Entry;
use Statamic\Extend\Tags;
use Illuminate\Support\Collection;

class GenerateEventsTags extends Tags
{
    /** @var Generator */
    private $generator;

    /** @var Collection */
    private $events;

    private $offset;

    private $limit;

    private $paginated;

    private $paginationData;

    public function __construct()
    {
        parent::__construct();

        $this->events = collect();
        $this->generator = new Generator();

        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);
    }

    public function nextEvents()
    {
        $this->limit = $this->getInt('limit', 1);
        $this->offset = $this->getInt('offset', 0);

        Entry::whereCollection($this->get('collection'))
            ->each(
                function ($event) {
                    $this->generator->add($event->toArray());
                }
            );

        if ($this->getBool('paginate')) {
            $this->paginate();
        } else {
            $this->events = $this->generator->nextXOccurrences($this->limit, $this->offset);
        }

        return $this->output();
    }

    public function calendar()
    {
        Entry::whereCollection($this->getParam('collection'))
            ->each(function ($event) {
                $this->generator->add($event->toArray());
            });

        $month = carbon($this->getParam('month', Carbon::now()))
            ->year($this->getParam('year', Carbon::now()->year));

        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $this->events = $this->generator
            ->all($from, $to)
            ->groupBy(function ($event, $key) {
                return carbon($event['next_date'])->toDateString();
            })
            ->map(function ($days, $key) {
                return [
                    'date' => $key,
                    'events' => $days->toArray(),
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

        return $this->parseLoop(array_merge($dates, $this->events->toArray()));
    }

    private function paginate()
    {
        $this->paginated = true;

        $page = (int) Request::get('page', 1);

        $this->offset = (($page - 1) * $this->limit) + $this->offset;

        $events = $this->generator->nextXOccurrences($this->limit + 1, $this->offset);

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

        $this->events = $events->slice(0, $this->limit);
    }

    private function output()
    {
        $data = array_merge(
            $this->getEventsMetaData(),
            ['events' => $this->events->toArray()]
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
            'total_results' => $this->events->count(),
        ];
    }
}
