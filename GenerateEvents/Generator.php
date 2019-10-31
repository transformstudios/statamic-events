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

    public function nextDate($event, $afterDate)
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
            case 'none':
                $nextOccurrence = null;
                break;
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

    /**
     * Get next `$limit` events, starting at `$offset`
     *
     * @todo This loop/if feels goopy. There's likely a nicer solution
     *
     * @param array $event
     * @param integer $occurrences
     * @param integer $offset
     * @return array
     */
    private function nextEvents($event, $limit, $offset = 0)
    {
        $currentDate = $this->nextDate($event, Carbon::now());

        $events = [];
        $x = 0;
        $total = $offset ? $limit * $offset : $limit;

        while ($currentDate && ($x < $total)) {
            $events[$x] = $event;
            $events[$x++]['next_date'] = $currentDate->toString();
            $currentDate = $this->nextDate($event, $currentDate);
        }

        return array_splice($events, $offset, $limit);
    }

    private function eventsBetween(array $event, Carbon $from, Carbon $to)
    {
        if ($from->startOfDay() > $to->endOfDay()) {
            return [];
        }

        // @TODO refactor - use collection and return collection
        //loop until currentDate > $to
        $currentDate = $this->nextDate($event, $from);
        $events = [];
        $x = 0;

        while ($currentDate && ($currentDate < $to)) {
            $events[$x] = $event;
            $events[$x++]['next_date'] = $currentDate->toString();
            $currentDate = $this->nextDate($event, $currentDate);
        }

        // end refactor

        return $events;
    }

    public function nextXOccurrences($limit = 1, $offset)
    {
        $events = $this->events->flatMap(function ($event, $ignore) use ($limit, $offset) {
            return $this->nextEvents($event, $limit, $offset);
        })->sortBy(function ($event, $ignore) {
            return carbon($event['next_date']);
        })->take($limit)
        ->values();

        if ($limit === 1) {
            return $events->first();
        }

        return $events;
    }

    public function all($from, $to)
    {
        return $this->events->flatMap(function ($event, $ignore) use ($from, $to) {
            return $this->eventsBetween($event, $from, $to);
        })->filter()
        ->sortBy(function ($event, $ignore) {
            return carbon($event['next_date']);
        })->values();
    }
}
