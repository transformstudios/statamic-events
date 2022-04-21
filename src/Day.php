<?php

namespace TransformStudios\Events;

use Carbon\CarbonImmutable;
use Spatie\IcalendarGenerator\Components\Event;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class Day
{
    private ?string $startTime;
    private ?string $endTime;
    private CarbonImmutable $date;

    public function __construct(array $data, private bool $isAllDay = false)
    {
        $this->date = CarbonImmutable::parse(Arr::get($data, 'date'));
        $this->startTime = Arr::get($data, 'start_time');
        $this->endTime = Arr::get($data, 'end_time');
    }

    public function hasEndtime(): bool
    {
        return boolval($this->endTime);
    }

    public function isAllDay(): bool
    {
        return $this->isAllDay;
    }

    public function start(): CarbonImmutable
    {
        return $this->isAllDay ? $this->date->startOfDay() : $this->date->setTimeFromTimeString($this->startTime);
    }

    public function end(): CarbonImmutable
    {
        if ($this->isAllDay || ! $this->endTime) {
            // RRules can't have microseconds
            return $this->date->endOfDay()->setMicrosecond(0);
        }

        return $this->date->setTimeFromTimeString($this->endTime);
    }

    public function toICalendarEvent(string $title, int $index = 0): Event
    {
        return Event::create($title)
            ->uniqueIdentifier(Str::slug($title).'-'.$index)
            ->startsAt($this->start())
            ->endsAt($this->end());
    }
}
