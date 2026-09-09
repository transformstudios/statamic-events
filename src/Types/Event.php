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
use TransformStudios\Events\Events;

abstract class Event
{
    abstract protected function rule(bool $useEnd = false): RRuleInterface;

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
            'daily', 'weekly', 'monthly', 'yearly', 'every' => true,
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
        return $this->collect($this->rule(true)->getOccurrencesAfter(date: now(), inclusive: true, limit: $limit));
    }

    public function startTime(): string
    {
        return $this->start_time ?? now()->startOfDay()->toTimeString('second');
    }

    public function start(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->start_date)
            ->shiftTimezone($this->timezoneName())
            ->setTimeFromTimeString($this->startTime());
    }

    public function end(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->start_date)
            ->shiftTimezone($this->timezoneName())
            ->setTimeFromTimeString($this->endTime());
    }

    public function toICalendarEvent(string|CarbonInterface $date): ?ICalendarEvent
    {
        if (! $this->occursOnDate($date)) {
            return null;
        }

        return $this->decorate($this->buildICalendarEvent($date));
    }

    /**
     * @return ICalendarEvent[]
     */
    public function toICalendarEvents(): array
    {
        if (! $event = $this->toICalendarEvent($this->start())) {
            return [];
        }

        return [$event];
    }

    protected function buildICalendarEvent(string|CarbonInterface $date): ICalendarEvent
    {
        $immutableDate = $this->toCarbonImmutable($date);

        return ICalendarEvent::create($this->event->title)
            ->withoutTimezone()
            ->uniqueIdentifier($this->event->id())
            ->startsAt($immutableDate->setTimeFromTimeString($this->startTime()))
            ->endsAt($immutableDate->setTimeFromTimeString($this->endTime()));
    }

    protected function decorate(ICalendarEvent $iCalEvent): ICalendarEvent
    {
        if ($location = $this->icsLocation()) {
            $iCalEvent->address($location);
        }

        if ($this->hasValidCoordinates($coords = Arr::get($this->event->get('location'), 'coordinates'))) {
            $iCalEvent->coordinates((float) $coords['latitude'], (float) $coords['longitude']);
        }

        if (! is_null($description = $this->event->description)) {
            $iCalEvent->description($description);
        }

        if (! is_null($url = $this->icsUrl())) {
            $iCalEvent->url($url);
        }

        return $iCalEvent;
    }

    // Entry::get() is untyped; keep mixed so a bad value can't TypeError the public ICS route.
    protected function hasValidCoordinates(mixed $coords): bool
    {
        return is_array($coords)
            && is_numeric($coords['latitude'] ?? null)
            && is_numeric($coords['longitude'] ?? null);
    }

    protected function icsLocation(): ?string
    {
        $name = Arr::get($this->event->get('location'), 'name');

        if (is_string($name) && $name !== '') {
            return $name;
        }

        return $this->icsUrl();
    }

    protected function icsUrl(): ?string
    {
        $url = $this->event->get('online_url');

        return is_string($url) && $url !== '' ? $url : null;
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

        return $carbon->shiftTimezone($this->timezoneName());
    }

    protected function timezoneName(): string
    {
        return Arr::get($this->timezone->data(), 'name') ?? Events::defaultTimezone();
    }

    private function collect(array $dates): Collection
    {
        return collect($dates)
            ->map(fn (DateTimeInterface $date) => $this->supplement(
                date: CarbonImmutable::parse($date, $this->timezoneName())
            ))->filter();
    }
}
