<?php

namespace Statamic\Addons\Events;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class Events
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

    public static function nextDate($event, $afterDate)
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
                $date = null;
                break;
            case 'daily':
                $date = $afterDate
                    ->day($afterDate->day)
                    ->hour($startDate->hour)
                    ->minute($startDate->minute)
                    ->second($startDate->second)
                    ->addDay();
            break;
            case 'weekly':
                $date = $afterDate
                    ->modify("next {$startDate->englishDayOfWeek}")
                    ->hour($startDate->hour)
                    ->minute($startDate->minute)
                    ->second($startDate->second);
                break;
            case 'monthly':
                $date = $startDate
                    ->month($afterDate->month)
                    ->year($afterDate->year);
                if ($afterDate->day >= $startDate->day) {
                    $date->addMonth();
                }
                break;
            case 'every_x_weeks':
                throw \Exception('not implemented');
        }

        return $date;
    }

    /**
     * Get next `$limit` dates, starting at `$offset`
     *
     * @todo This loop/if feels goopy. There's likely a nicer solution
     *
     * @param array $event
     * @param integer $occurrences
     * @param integer $offset
     * @return array
     */
    private static function nextDates($event, $limit, $offset = 0)
    {
        $currentDate = self::nextDate($event, Carbon::now());

        $dates = [];
        $x = 0;
        $total = $offset ? $limit * $offset : $limit;

        while ($currentDate && ($x < $total)) {
            $dates[$x] = $event;
            $dates[$x++]['date'] = $currentDate->toString();
            $currentDate = self::nextDate($event, $currentDate);
        }

        return array_splice($dates, $offset, $limit);
    }

    private function eventsBetween(array $event, Carbon $from, Carbon $to)
    {
        if ($from->startOfDay() > $to->endOfDay()) {
            return [];
        }

        // @TODO refactor - use collection and return collection
        //loop until currentDate > $to
        $currentDate = self::nextDate($event, $from);
        $events = [];
        $x = 0;

        while ($currentDate && ($currentDate < $to)) {
            $events[$x] = $event;
            $events[$x++]['date'] = $currentDate->toString();
            $currentDate = self::nextDate($event, $currentDate);
        }

        // end refactor

        return $events;
    }

    public function next($limit = 1, $offset = 0)
    {
        $events = $this->events->flatMap(function ($event, $ignore) use ($limit, $offset) {
            return self::nextDates($event, $limit, $offset);
        })->sortBy(function ($event, $ignore) {
            return carbon($event['date']);
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
            return carbon($event['date']);
        })->values();
    }
}
