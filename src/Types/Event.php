<?php

namespace TransformStudios\Events\Types;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Collection;
use RRule\RRuleInterface;
use Spatie\IcalendarGenerator\Components\Event as ICalendarEvent;
use Statamic\Entries\Entry;

abstract class Event
{
    abstract protected function rule(): RRuleInterface;

    public function __construct(protected Entry $event)
    {
    }

    public function __get(string $key): mixed
    {
        return $this->event->$key;
    }

    public function endTime(): string
    {
        return $this->end_time ?? now()->endOfDay()->toTimeString();
    }

    public function hasEndTime(): bool
    {
        return boolval($this->end_time);
    }

    public function isAllDay(): bool
    {
        return boolval($this->all_day);
    }

    public function isMultiDay(): bool
    {
        return boolval($this->multi_day);
    }

    public function isRecurring(): bool
    {
        // this is a select field so you have to get its value
        return boolval($this->recurrence?->value());
    }

    public function occurrencesBetween(string|CarbonInterface $from, string|CarbonInterface $to): Collection
    {
        return $this->collect($this->rule()->getOccurrencesBetween(begin: $from, end: $to));
    }

    public function occursOnDate(string|CarbonInterface $date): bool
    {
        $immutableDate = is_string($date) ? CarbonImmutable::parse($date) : $date->toImmutable();

        return ! empty($this->rule()->getOccurrencesBetween(begin: $immutableDate->startOfDay(), end: $immutableDate->endOfDay()));
    }

    public function nextOccurrences(int $limit = 1): Collection
    {
        return $this->collect($this->rule()->getOccurrencesAfter(date: now(), inclusive: true, limit: $limit));
    }

    public function startTime(): string
    {
        return $this->start_time ?? now()->startOfDay()->toTimeString('second');
    }

    public function start(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->start_date)
            ->setTimeFromTimeString($this->startTime());
    }

    public function toICalendarEvent(string|CarbonInterface $date): ?ICalendarEvent
    {
        if (! $this->occursOnDate($date)) {
            return null;
        }

        $immutableDate = is_string($date) ? CarbonImmutable::parse($date) : $date->toImmutable();

        return ICalendarEvent::create($this->event->title)
            ->uniqueIdentifier($this->event->id())
            ->startsAt($immutableDate->setTimeFromTimeString($this->startTime()))
            ->endsAt($immutableDate->setTimeFromTimeString($this->endTime()));
    }

    /**
     * @return ICalendarEvent[]
     */
    public function toICalendarEvents(): array
    {
        return [];
    }

    protected function supplement(CarbonInterface $date): Entry
    {
        return unserialize(serialize($this->event))
            ->setSupplement('start', $date->setTimeFromTimeString($this->startTime()))
            ->setSupplement('end', $date->setTimeFromTimeString($this->endTime()))
            ->setSupplement('has_end_time', $this->hasEndTime());
    }

    private function collect(array $dates): Collection
    {
        return collect($dates)
            ->map(fn (DateTimeInterface $date) => $this->supplement(date: CarbonImmutable::parse($date)));
    }
}
