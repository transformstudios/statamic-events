<?php

namespace TransformStudios\Events\Types;

use Carbon\Carbon;
use Statamic\API\Arr;
use Illuminate\Support\Collection;
use Statamic\Addons\Events\Schedule;

class MultiDayEvent extends Event
{
    /** @var Collection */
    private $days;

    public function __construct($data)
    {
        parent::__construct($data);

        $isAllDay = $this->isAllDay();

        $this->days = collect(Arr::get($data, 'days', []))
            ->map(function ($day, $ignore) use ($isAllDay) {
                return new Schedule($day, $isAllDay);
            });
    }

    public function isMultiDay(): bool
    {
        return true;
    }

    public function start(): Carbon
    {
        return $this->firstDay()->start();
    }

    public function end(): Carbon
    {
        return $this->lastDay()->end();
    }

    public function firstDay(): Schedule
    {
        return $this->days()->first();
    }

    public function lastDay(): Schedule
    {
        return $this->days()->last();
    }

    /**
     * @param null|Carbon $after
     */
    public function upcomingDate($after = null): ?Schedule
    {
        if (is_null($after)) {
            $after = Carbon::now();
        }

        $first = $this->firstDay();
        $end = $this->lastDay()->end();

        if ($this->asSingleDay) {
            $first->endDate($end);
        }

        if ($after < $first->start() || ($this->asSingleDay && $after <= $end)) {
            return $first;
        }

        if ($after > $end) {
            return null;
        }

        return $this
            ->days()
            ->first(function ($ignore, $day) use ($after) {
                return $after < $day->start();
            });
    }

    public function upcomingDates($limit = 2, $offset = 0): Collection
    {
        $total = $offset ? $limit * $offset : $limit;

        $dates = collect();

        $day = Schedule::now();

        if ($this->asSingleDay) {
            return collect([$this->upcomingDate(Carbon::now())]);
        }

        while (($day = $this->upcomingDate($day->start())) && $dates->count() <= $total) {
            $dates->push($day);
        }

        return $dates->slice($offset, $limit);
    }

    public function datesBetween($from, $to): Collection
    {
        $from = carbon($from);
        $to = carbon($to);

        if (($from->startOfDay() > $to->endOfDay()) ||
            ($this->start()->isAfter($to)) ||
            ($this->end()->isBefore($from))
        ) {
            return collect();
        }

        $days = collect();
        $day = $this->upcomingDate($from);

        while ($day && $day->start() < $to) {
            $days->push($day);
            $day = $this->upcomingDate($day->start());
        }

        return $days;
    }

    private function days()
    {
        return collect($this->days)
            ->sortBy(
                function ($day) {
                    return $day->start();
                }
            );
    }
}
