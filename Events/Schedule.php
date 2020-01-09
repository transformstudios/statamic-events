<?php

namespace Statamic\Addons\Events;

use Carbon\Carbon;
use Statamic\API\Arr;

class Schedule
{
    private $date;

    private $startTime;

    private $endTime;

    private $endDate;

    public function __construct($data, bool $isAllDay = false)
    {
        $this->date = $data['date'];
        $this->endDate = Arr::get($data, 'end_date', $this->date);

        if ($isAllDay) {
            $date = carbon($this->date);
            $this->startTime = $date->startOfDay()->format('G:i');
            $this->endTime = $date->endOfDay()->format('G:i');
        } else {
            $this->startTime = $data['start_time'];
            $this->endTime = $data['end_time'];
        }
    }

    public function start(): Carbon
    {
        return carbon($this->date)->setTimeFromTimeString($this->startTime);
    }

    public function startDate(): string
    {
        return $this->date;
    }

    public function startTime(): string
    {
        return $this->startTime;
    }

    public function end(): Carbon
    {
        return carbon($this->endDate)->setTimeFromTimeString($this->endTime);
    }

    public function endDate($date = null)
    {
        if (is_null($date)) {
            return $this->endDate;
        }

        if ($date instanceof Carbon) {
            $date = $date->toDateString();
        }

        $this->endDate = $date;

        return $this;
    }

    public function endTime(): string
    {
        return $this->endTime;
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
