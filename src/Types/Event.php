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
        $dates = $this->rule()->getOccurrencesBetween($from, $to);

        return $this->collect($dates);
    }

    public function nextOccurrences(int $limit = 1): Collection
    {
        $dates = $this->rule()->getOccurrencesAfter(now(), true, $limit);

        return $this->collect($dates);
    }

    private function collect(array $dates): Collection
    {
        return collect($dates)
            ->map(fn (DateTimeInterface $date) => $this->supplement(CarbonImmutable::parse($date)));
    }

    private function supplement(CarbonInterface $date): Entry
    {
        $occurrence = unserialize(serialize($this->event));
        $occurrence->setSupplement('start', $date->setTimeFromTimeString($this->startTime()));

        if ($endTime = $this->end_time) {
            $occurrence->setSupplement('end', $date->setTimeFromTimeString($endTime));
        }

        return $occurrence;
    }

    public function __get(string $key): mixed
    {
        return $this->event->get($key);
    }

    // Should these be somewhere else?
    public function hasEndTime(): bool
    {
        return $this->event->get('end_time', false);
    }

    public function isAllDay(): bool
    {
        return $this->event->get('all_day', false);
    }

    public function isMultiDay(): bool
    {
        return $this->event->get('multi_day', false);
    }

    public function isRecurring(): bool
    {
        return $this->event->get('recurrence', false);
    }

    public function startTime(): string
    {
        return $this->start_time ?? now()->startOfDay()->format('G:i');
    }

    public function endTime(): string
    {
        return $this->end_time ?? now()->endOfDay()->format('G:i');
    }

    public function start(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->start_date)
            ->setTimeFromTimeString($this->startTime());
    }

    abstract public function end(): ?CarbonImmutable;
}
