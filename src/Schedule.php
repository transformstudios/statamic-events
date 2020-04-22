<?php

namespace TransformStudios\Events;

use Carbon\Carbon;
use Statamic\Support\Arr;

class Schedule
{
    private $date;

    private $startTime;

    private $endTime;

    private $endDate;

    public function __construct($data, bool $isAllDay = false)
    {
        $this->date = Arr::get($data, 'date', Carbon::now()->toDateString());
        $this->endDate = Arr::get($data, 'end_date', $this->date);

        if ($isAllDay) {
            $date = Carbon::parse($this->date);
            $this->startTime = $date->startOfDay()->format('G:i');
            $this->endTime = $date->endOfDay()->format('G:i');
        } else {
            $this->startTime = Arr::get($data, 'start_time', Carbon::parse($this->date)->startOfDay()->toTimeString());
            $this->endTime = Arr::get($data, 'end_time', Carbon::parse($this->date)->endOfDay()->toTimeString());
        }
    }

    public function start(): Carbon
    {
        return Carbon::parse($this->date)->setTimeFromTimeString($this->startTime);
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
        return Carbon::parse($this->endDate)->setTimeFromTimeString($this->endTime);
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

    public static function now(): self
    {
        return self::fromCarbon(Carbon::now());
    }

    public static function fromCarbon(Carbon $date): self
    {
        return new self(
            [
            'date' => $date->toDateString(),
            'start_time' => $date->toTimeString(),
            'end_time' => $date->endOfDay()->toTimeString(),
            ]
        );
    }
}
