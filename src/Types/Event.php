<?php

namespace TransformStudios\Events\Types;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Statamic\Fields\Value;
use Statamic\Support\Arr;
use TransformStudios\Events\Day;

abstract class Event implements Arrayable
{
    /** @var array */
    protected $data;

    abstract public function upcomingDate($after = null): ?Day;

    abstract public function upcomingDates($limit = 2, $offset = 0): Collection;

    abstract public function datesBetween($from, $to): Collection;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function get($name, $default = null)
    {
        return $this->raw($this->data, $name, $default);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value)
    {
        return Arr::set($this->data, $name, $value);
    }

    public function isAllDay(): bool
    {
        return $this->raw($this->data, 'all_day', false);
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

        return $this->raw($this->data, 'start_time', $time);
    }

    public function endTime(): string
    {
        $time = Carbon::now()->endOfDay()->format('G:i');
        if ($this->isAllDay()) {
            return $time;
        }

        return $this->raw($this->data, 'end_time', $time);
    }

    public function start(): Carbon
    {
        return Carbon::parse($this->raw($this->data, 'start_date'))->setTimeFromTimeString($this->startTime());
    }

    public function end(): ?Carbon
    {
        return Carbon::parse(Arr::get($this->data, 'start_date'))->setTimeFromTimeString($this->endTime());
    }

    public function toArray(): array
    {
        return $this->data;
    }

    protected function raw($data, $key, $default = null)
    {
        $value = Arr::get($data, $key, $default);

        if ($value instanceof Value) {
            return $value->raw() ?: $default;
        }

        return $value ?: $default;
    }
}
