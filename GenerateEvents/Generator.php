<?php

namespace Statamic\Addons\GenerateEvents;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class Generator
{
    /** @var Collection */
    private $events;

    public function __construct()
    {
        $this->events = collect();
    }

    public function add($event)
    {
        $this->events->push($event);
    }

    public function nextDate($event, $afterDate): ?Carbon
    {
        $afterDate = carbon($afterDate ?? time())->copy();
        $startDate = carbon($event['start_date'])->copy();
        $endDate = isset($event['end_date']) ? carbon($event['end_date'])->copy() : null;

        if ($afterDate < $startDate) {
            return $startDate;
        }

        if (!isset($event['recurrence']) || ($endDate && $afterDate >= $endDate)) {
            return null;
        }

        switch ($event['recurrence']) {
            case 'daily':
                $nextOccurrence = $startDate
                    ->day($afterDate->day)
                    ->addDay();
            break;
            case 'weekly':
                $nextOccurrence = $afterDate
                    ->modify("next {$startDate->englishDayOfWeek}")
                    ->hour($startDate->hour)
                    ->minute($startDate->minute)
                    ->second($startDate->second);
                break;
            case 'monthly':
                $nextOccurrence = $startDate
                    ->month($afterDate->month)
                    ->year($afterDate->year);
                if ($afterDate->day >= $startDate->day) {
                    $nextOccurrence->addMonth();
                }
                break;
            case 'every_x_weeks':
                throw \Exception('not implemented');
        }

        return $nextOccurrence;
    }

    public function nextXEvents($event, $occurrences)
    {
        $currentDate = $this->nextDate($event, Carbon::now());

        $events = [];
        $x = 0;

        while ($currentDate && ($x < $occurrences)) {
            $events[$x] = $event;
            $events[$x++]['next_date'] = $currentDate->toString();
            $currentDate = $this->nextDate($event, $currentDate);
        }

        return $events;
    }

    public function occurrencesUntil(Carbon $afterDate, Carbon $untilDate, array $event)
    {
        if ($afterDate > $untilDate) {
            return null;
        }

        // @TODO refactor
        //loop until currentDate > $untilDate
        $currentDate = $this->nextDate($event, $afterDate);
        $events = [];
        $x = 0;

        while ($currentDate && ($currentDate < $untilDate)) {
            $events[$x] = $event;
            $events[$x++]['next_date'] = $currentDate->toString();
            $currentDate = $this->nextDate($event, $currentDate);
        }

        // end refactor

        return $events;
    }

    public function nextXOccurrences($occurrences, $afterDate = null)
    {
        return $this->events->flatMap(function ($event, $key) use ($occurrences) {
            return $this->nextXEvents($event, $occurrences);
        })->sortBy(function ($event, $key) {
            return carbon($event['next_date']);
        })->take($occurrences)
        ->values();
    }

    public function all($afterDate, $untilDate)
    {
        return $this->events->flatMap(function ($event, $key) use ($afterDate, $untilDate) {
            return $this->occurrencesUntil($afterDate, $untilDate, $event);
        })->sortBy(function ($event, $key) {
            return carbon($event['next_date']);
        })->values();
    }
}
