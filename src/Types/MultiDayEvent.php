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
use Statamic\Support\Str;
use TransformStudios\Events\Day;

class MultiDayEvent extends Event
{
    private Collection $days;

    public function __construct(Entry $event, private bool $collapseMultiDays)
    {
        parent::__construct($event);

        $this->days = collect($this->event->days)
            ->sortBy('date')
            ->map(fn (Values $day) => new Day(
                $day->all(),
                $this->timezoneName(),
                $day->all_day || $this->isAllDay(),
            ));
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

    public function nextOccurrences(int $limit = 1): Collection
    {
        return $this->uniqueCollapsedOccurrences(parent::nextOccurrences($limit));
    }

    public function occurrencesBetween(string|CarbonInterface $from, string|CarbonInterface $to): Collection
    {
        return $this->uniqueCollapsedOccurrences(parent::occurrencesBetween($from, $to));
    }

    public function start(): CarbonImmutable
    {
        return $this->days->first()->start();
    }

    /**
     * @return ICalendarEvent[]
     */
    public function toICalendarEvents(): array
    {
        return $this->days
            ->values()
            ->map(function (Day $day, int $index) {
                $event = $this->toICalendarEvent($day->start());

                return $event?->uniqueIdentifier(Str::slug($this->event->title).'-'.$index);
            })
            ->filter()
            ->all();
    }

    protected function buildICalendarEvent(string|CarbonInterface $date): ICalendarEvent
    {
        $immutableDate = $this->toCarbonImmutable($date);
        $day = $this->getDayFromDate($immutableDate);

        return ICalendarEvent::create($this->event->title)
            ->uniqueIdentifier($this->event->id())
            ->startsAt($immutableDate->setTimeFromTimeString($day->start()))
            ->endsAt($immutableDate->setTimeFromTimeString($day->end()));
    }

    protected function rule(bool $useEnd = false): RRuleInterface
    {
        return tap(
            new RSet,
            fn (RSet $rset) => $this->days->each(fn (Day $day) => $rset->addRRule([
                'count' => 1,
                'dtstart' => $day->end()->subSecond(),
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
                    ->setSupplement('multi_day', true)
                    ->setSupplement('collapse_multi_days', true)
                    ->setSupplement('start', $this->start())
                    ->setSupplement('end', $this->end())
                    ->setSupplement('has_end_time', $this->hasEndTime())
            );
        }

        return tap(
            unserialize(serialize($this->event)),
            fn (Entry $occurrence) => $occurrence
                ->setSupplement('all_day', $day->isAllDay())
                ->setSupplement('multi_day', true)
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

    private function uniqueCollapsedOccurrences(Collection $occurrences): Collection
    {
        if (! $this->collapseMultiDays) {
            return $occurrences;
        }

        return $occurrences->unique(fn (Entry $occurrence) => $occurrence->id())->values();
    }
}
