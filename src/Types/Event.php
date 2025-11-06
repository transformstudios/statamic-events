<?php

namespace TransformStudios\Events\Types;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use RRule\RRuleInterface;
use Spatie\IcalendarGenerator\Components\Event as ICalendarEvent;
use Statamic\Entries\Entry;

abstract class Event
{
    abstract protected function rule(): RRuleInterface;

    public function __construct(protected Entry $event) {}

    public function __get(string $key): mixed
    {
        return $this->event->$key;
    }

    /*
        This is needed so that empty($event->days) works. This is due to how PHP handles
        `empty`: it gets translated to
        `!$class->__isset('property') || empty($class->__get('property')))`
    */
    public function __isset(string $key): bool
    {
        return isset($this->event->$key);
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
        return boolval(($this->multi_day || $this->recurrence?->value() === 'multi_day') && ! empty($this->days));
    }

    public function isRecurring(): bool
    {
        // this is a select field so you have to get its value
        return match ($this->recurrence?->value()) {
            'daily', 'weekly', 'monthly', 'yearly' => true,
            default => false,
        };
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
            ->shiftTimezone($this->timezone['name'])
            ->setTimeFromTimeString($this->startTime());
    }

    public function end(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->start_date)
            ->shiftTimezone($this->timezone['name'])
            ->setTimeFromTimeString($this->endTime());
    }

    public function toICalendarEvent(string|CarbonInterface $date): ?ICalendarEvent
    {
        if (! $this->occursOnDate($date)) {
            return null;
        }

        $immutableDate = $this->toCarbonImmutable($date);

        $iCalEvent = ICalendarEvent::create($this->event->title)
            ->withoutTimezone()
            ->uniqueIdentifier($this->event->id())
            ->startsAt($immutableDate->setTimeFromTimeString($this->startTime()))
            ->endsAt($immutableDate->setTimeFromTimeString($this->endTime()));

        if (! is_null($address = $this->event->address ?? $this->location($this->event))) {
            $iCalEvent->address($address);
        }

        if (! is_null($coords = $this->event->coordinates)) {
            $iCalEvent->coordinates($coords['latitude'], $coords['longitude']);
        }

        if (! is_null($description = $this->event->description)) {
            $iCalEvent->description($description);
        }

        if (! is_null($link = $this->event->link)) {
            $iCalEvent->url($link);
        }

        return $iCalEvent;
    }

    /**
     * @return ICalendarEvent[]
     */
    public function toICalendarEvents(): array
    {
        return Arr::wrap($this->toICalendarEvent($this->start()));
    }

    protected function location(Entry $event): ?string
    {
        $collectionHandle = $event->collectionHandle();

        $locationField = config("events.collections.$collectionHandle.location_field", 'location');

        if (is_null($location = $event->{$locationField})) {
            return null;
        }

        if (! is_string($location)) {
            return null;
        }

        return $location;
    }

    protected function supplement(CarbonInterface $date): ?Entry
    {
        return unserialize(serialize($this->event))
            ->setSupplement('multi_day', false)
            ->setSupplement('start', $date->setTimeFromTimeString($this->startTime()))
            ->setSupplement('end', $date->setTimeFromTimeString($this->endTime()))
            ->setSupplement('has_end_time', $this->hasEndTime());
    }

    protected function toCarbonImmutable(string|CarbonInterface $date): CarbonImmutable
    {
        $carbon = is_string($date) ? CarbonImmutable::parse($date) : $date;

        return $carbon->shiftTimezone($this->timezone['name']);
    }

    private function collect(array $dates): Collection
    {
        return collect($dates)
            ->map(fn (DateTimeInterface $date) => $this->supplement(
                date: CarbonImmutable::parse($date, $this->timezone['name'])
            ))->filter();
    }
}
