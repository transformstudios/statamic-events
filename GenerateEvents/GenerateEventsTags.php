<?php

namespace Statamic\Addons\GenerateEvents;

use Carbon\Carbon;
use Statamic\API\Arr;
use Statamic\API\Entry;
use Statamic\Extend\Tags;

class GenerateEventsTags extends Tags
{
    /** @var Generator */
    private $generator;

    public function __construct()
    {
        parent::__construct();

        $this->generator = new Generator();

        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);
    }

    /**
     * The {{ generate_events }} tag
     *
     * @return string|array
     */
    // public function nextOccurrence()
    // {
    //     if ($recurrenceType = Arr::get($this->context, 'recurrence')) {
    //         $startDate = carbon(Arr::get($this->context, 'start_date'));
    //         $generator = new Generator(
    //             $startDate,
    //             $recurrenceType,
    //             Arr::get($this->context, 'end_date')
    //         );

    //         $nextOccurrence = $generator->nextEvent(time());

    //         return $nextOccurrence;
    //     }
    // }

    public function nextEvents()
    {
        Entry::whereCollection($this->getParam('collection'))->each(function ($event) {
            $this->generator->add($event->toArray());
        });

        return $this->parseLoop(
            $this->generator->nextXOccurrences($this->getParamInt('limit', 1))->toArray()
        );
    }

    // public function all()
    // {
    //     Entry::whereCollection($this->getParam('collection'))->each(function ($event) {
    //         $this->generator->add($event->toArray());
    //     });

    //     $from = carbon($this->getParam('from', Carbon::now()));
    //     $to = carbon($this->getParam('to'));

    //     $events = $this->generator
    //         ->all($from, $to)
    //         ->groupBy(function ($event, $key) {
    //             return carbon($event['next_date'])->toDateString();
    //         })
    //         ->map(function ($days, $key) {
    //             return [
    //                 'date' => $key,
    //                 'events' => $days->toArray(),
    //             ];
    //         })
    //         ->toArray();

    //     // generate an array of dates from/to
    //     $days_to_render = $to->diffInDays($from);

    //     $dates = [];

    //     for ($i = 0; $i <= $days_to_render; $i++) {
    //         $date = $from->toDateString();
    //         $dates[$date] = [
    //             'date' => $date,
    //             'no_results' => true,
    //         ];
    //         $from->addDay();
    //     }

    //     return $this->parseLoop(array_merge($dates, $events));
    // }

    public function calendar()
    {
        Entry::whereCollection($this->getParam('collection'))->each(function ($event) {
            $this->generator->add($event->toArray());
        });

        $month = carbon($this->getParam('month'));

        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $events = $this->generator
            ->all($from, $to)
            ->groupBy(function ($event, $key) {
                return carbon($event['next_date'])->toDateString();
            })
            ->map(function ($days, $key) {
                return [
                    'date' => $key,
                    'events' => $days->toArray(),
                ];
            })
            ->toArray();

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

        return $this->parseLoop(array_merge($dates, $events));
    }
}
