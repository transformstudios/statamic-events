<?php

namespace Statamic\Addons\Events;

use Carbon\Carbon;

class Schedule
{
    private string $date;

    private string $start;

    private string $end;

    public function __construct($data, bool $isAllDay = false)
    {
        $this->date = $data['date'];

        if ($isAllDay) {
            $date = carbon($this->date);
            $this->start = $date->startOfDay()->format('G:i');
            $this->end = $date->endOfDay()->format('G:i');
        } else {
            $this->start = $data['start_time'];
            $this->end = $data['end_time'];
        }
    }

    public function start(): Carbon
    {
        return carbon($this->date)->setTimeFromTimeString($this->start);
    }

    public function startTime(): string
    {
        return $this->start;
    }

    public function end(): Carbon
    {
        return carbon($this->date)->setTimeFromTimeString($this->end);
    }

    public function endTime(): string
    {
        return $this->end;
    }

    public static function now(): Schedule
    {
        return Schedule::fromCarbon(Carbon::now());
    }

    public static function fromCarbon(Carbon $date): Schedule
    {
        return new Schedule(
            [
            'date' => $date->toDateString(),
            'start_time' => $date->toTimeString(),
            'end_time' => $date->endOfDay()->toTimeString(),
            ]
        );
    }
}