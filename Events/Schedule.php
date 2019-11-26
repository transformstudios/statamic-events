<?php

namespace Statamic\Addons\Events;

use Carbon\Carbon;

class Schedule
{
    private $date;

    private $start;

    private $end;

    public function __construct(string $date, string $startTime, string $endTime)
    {
        $this->date = $date;

        $this->start = $startTime;

        $this->end = $endTime;
    }

    public function start(): Carbon
    {
        return carbon($this->date)->setTimeFromTimeString($this->start);
    }

    public function end(): Carbon
    {
        return carbon($this->date)->setTimeFromTimeString($this->end);
    }

    public static function now(): Schedule
    {
        return Schedule::fromCarbon(Carbon::now());
    }

    public static function fromCarbon($date): Schedule
    {
        return new Schedule(
            $date->toDateString(),
            $date->toTimeString(),
            $date->endOfDay()->toTimeString()
        );
    }
}