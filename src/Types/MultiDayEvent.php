<?php

namespace TransformStudios\Events\Types;

use Carbon\Carbon;
use Statamic\Support\Arr;
use TransformStudios\Events\Day;
use Illuminate\Support\Collection;

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
                return new Day($day, $isAllDay);
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

    public function firstDay(): Day
    {
        return $this->days()->first();
    }

    public function lastDay(): Day
    {
        return $this->days()->last();
    }

    /**
     * @param null|Carbon $after
     */
    public function upcomingDate($after = null): ?Day
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
            ->first(function ($day, $ignore) use ($after) {
                return $after < $day->start();
            });
    }

    public function upcomingDates($limit = 2, $offset = 0): Collection
    {
        $total = $offset ? $limit * $offset : $limit;

        $dates = collect();

        $day = Day::now();

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
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);

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
