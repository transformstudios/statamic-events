<?php

namespace TransformStudios\Events\Types;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Collection;
use RRule\RRuleInterface;
use Statamic\Entries\Entry;

abstract class Event
{
    abstract protected function rule(): RRuleInterface;

    public function __construct(protected Entry $event)
    {
    }

    public function occurrencesBetween(string|CarbonInterface $from, string|CarbonInterface $to): Collection
    {
        return $this->collect($this->rule()->getOccurrencesBetween(begin: $from, end: $to));
    }

    public function nextOccurrences(int $limit = 1): Collection
    {
        return $this->collect($this->rule()->getOccurrencesAfter(date: now(), inclusive: true, limit: $limit));
    }

    private function collect(array $dates): Collection
    {
        return collect($dates)
            ->map(fn (DateTimeInterface $date) => $this->supplement(date: CarbonImmutable::parse($date)));
    }

    protected function supplement(CarbonInterface $date): Entry
    {
        return unserialize(serialize($this->event))
            ->setSupplement('start', $date->setTimeFromTimeString($this->startTime()))
            ->setSupplement('end', $date->setTimeFromTimeString($this->endTime()))
            ->setSupplement('has_end_time', $this->hasEndTime());
    }

    public function __get(string $key): mixed
    {
        return $this->event->$key;
    }

    public function hasEndTime(): bool
    {
        return boolval($this->event->end_time);
    }

    public function isAllDay(): bool
    {
        return boolval($this->event->all_day);
    }

    public function isMultiDay(): bool
    {
        return boolval($this->event->multi_day);
    }

    public function isRecurring(): bool
    {
        // this is a select field so you have to get its value
        return boolval($this->event->recurrence?->value());
    }

    public function startTime(): string
    {
        return $this->event->start_time ?? now()->startOfDay()->toTimeString('second');
    }

    public function endTime(): string
    {
        return $this->event->end_time ?? now()->endOfDay()->toTimeString('second');
    }

    public function start(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->event->start_date)
            ->setTimeFromTimeString($this->startTime());
    }
}
