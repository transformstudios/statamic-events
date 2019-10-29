<?php

namespace Statamic\Addons\GenerateEvents;

use Carbon\Carbon;
use Statamic\API\Entry;
use Statamic\Extend\Tags;
use Illuminate\Support\Collection;

class GenerateEventsTags extends Tags
{
    /** @var Collection */
    private $events;

    public function __construct()
    {
        parent::__construct();

        $this->events = collect();

        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);
    }

    public function nextEvents()
    {
        $generator = new Generator();

        Entry::whereCollection($this->getParam('collection'))
            ->each(
                function ($event) use ($generator) {
                    $generator->add($event->toArray());
                }
            );

        $this->events = $generator->nextXOccurrences(
            $this->getParamInt('limit', 1),
            $this->getParam('offset', 1)
        );

        return $this->output();
    }

    public function calendar()
    {
        $generator = new Generator();

        Entry::whereCollection($this->getParam('collection'))
            ->each(function ($event) use ($generator) {
                $generator->add($event->toArray());
            });

        $month = carbon($this->getParam('month', Carbon::now()))
            ->year($this->getParam('year', Carbon::now()->year));

        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $this->events = $generator
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

    protected function output()
    {
        if ($as = $this->get('as')) {
            $data = array_merge(
                [$as => $this->events->toArray()],
                $this->getEventsMetaData()
            );

            return $this->parse($data);
        } else {
            // Add the meta data (total_results, etc) into each iteration.
            $meta = $this->getEventsMetaData();
            $data = $this->events->map(function ($event) use ($meta) {
                return array_merge($event, $meta);
            })->all();

            return $this->parseLoop($data);
        }
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
