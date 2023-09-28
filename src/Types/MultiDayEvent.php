<?php

namespace TransformStudios\Events\Types;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use RRule\RRule;
use RRule\RRuleInterface;
use RRule\RSet;
use Spatie\IcalendarGenerator\Components\Event as ICalendarEvent;
use Statamic\Entries\Entry;
use Statamic\Fields\Values;
use TransformStudios\Events\Day;

class MultiDayEvent extends Event
{
    private Collection $days;

    public function __construct(Entry $event, private bool $collapseMultiDays)
    {
        parent::__construct($event);

        $this->days = collect($this->event->days)
            ->map(fn (Values $day) => new Day($day->all(), $this->isAllDay()));
    }

    /**
     * @template T of TransformStudios\Events\Day
     *
     * @return Collection<T>
     */
    public function days(): Collection
    {
        return $this->days;
    }

    public function end(): CarbonImmutable
    {
        return $this->days->last()->end();
    }

    public function start(): CarbonImmutable
    {
        return $this->days->first()->start();
    }

    public function toICalendarEvent(string|CarbonInterface $date): ?ICalendarEvent
    {
        if (! $this->occursOnDate($date)) {
            return null;
        }

        $immutableDate = $this->toCarbonImmutable($date);
        $day = $this->getDayFromDate($immutableDate);

        return ICalendarEvent::create($this->event->title)
            ->uniqueIdentifier($this->event->id())
            ->startsAt($immutableDate->setTimeFromTimeString($day->start()))
            ->endsAt($immutableDate->setTimeFromTimeString($day->end()));
    }

    /**
     * @return ICalendarEvent[]
     */
    public function toICalendarEvents(): array
    {
        return collect($this->days)
            ->map(fn (Day $day, int $index) => $day->toICalendarEvent($this->event->title, $index))
            ->all();
    }

    protected function rule(bool $collapseDays = false): RRuleInterface
    {
        // if we're collapsing, then return an rrule instead of rset and use start of first day to end of last day
        if ($this->collapseMultiDays) {
            return new RRule([
                'count' => 1,
                'dtstart' => $this->end()->timezone($this->timezone['timezone']),
                'freq' => RRule::DAILY,
            ]);
        }

        return tap(
            new RSet(),
            fn (RSet $rset) => $this->days->each(fn (Day $day) => $rset->addRRule([
                'count' => 1,
                'dtstart' => $day->end()->subSecond()->timezone($this->timezone['timezone']),
                'freq' => RRule::SECONDLY,
            ]))
        );
    }

    protected function supplement(CarbonInterface $date): ?Entry
    {
        if (! $day = $this->getDayFromDate($date)) {
            return null;
        }

        if ($this->collapseMultiDays) {
            return tap(
                unserialize(serialize($this->event)),
                fn (Entry $occurrence) => $occurrence
                    ->setSupplement('collapse_multi_days', true)
                    ->setSupplement('start', $this->start())
                    ->setSupplement('end', $this->end())
                    ->setSupplement('has_end_time', $this->hasEndTime())
            );
        }

        return tap(
            unserialize(serialize($this->event)),
            fn (Entry $occurrence) => $occurrence
                ->setSupplement('collapse_multi_days', $this->collapseMultiDays)
                ->setSupplement('start', $day->start())
                ->setSupplement('end', $day->end())
                ->setSupplement('has_end_time', $day->hasEndTime())
        );
    }

    private function getDayFromDate(CarbonInterface $date): ?Day
    {
        return $this->days->first(fn (Day $day, int $index) => $this->collapseMultiDays ? $index == 0 : $date->isSameDay($day->start()));
    }
}
