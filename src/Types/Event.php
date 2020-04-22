<?php

namespace TransformStudios\Events\Types;

use Carbon\Carbon;
use Statamic\Support\Arr;
use Illuminate\Support\Collection;
use TransformStudios\Events\Schedule;
use Illuminate\Contracts\Support\Arrayable;

abstract class Event implements Arrayable
{
    /** @var array */
    protected $data;

    abstract public function upcomingDate($after = null): ?Schedule;

    abstract public function upcomingDates($limit = 2, $offset = 0): Collection;

    abstract public function datesBetween($from, $to): Collection;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function get($name, $default = null)
    {
        return Arr::get($this->data, $name, $default);
    }

    public function __get($name)
    {
        return Arr::get($this->data, $name);
    }

    public function __set($name, $value)
    {
        return Arr::set($this->data, $name, $value);
    }

    public function isAllDay(): bool
    {
        return Arr::get($this->data, 'all_day', false);
    }

    public function isMultiDay(): bool
    {
        return false;
    }

    public function isRecurring(): bool
    {
        return false;
    }

    public function startTime(): string
    {
        $time = Carbon::now()->startOfDay()->format('G:i');
        if ($this->isAllDay()) {
            return $time;
        }

        return Arr::get($this->data, 'start_time', $time);
    }

    public function endTime(): string
    {
        $time = Carbon::now()->endOfDay()->format('G:i');
        if ($this->isAllDay()) {
            return $time;
        }

        return Arr::get($this->data, 'end_time', $time);
    }

    public function start(): Carbon
    {
        return Carbon::parse(Arr::get($this->data, 'start_date'))->setTimeFromTimeString($this->startTime());
    }

    public function end(): ?Carbon
    {
        return Carbon::parse(Arr::get($this->data, 'start_date'))->setTimeFromTimeString($this->endTime());
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
