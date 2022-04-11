<?php

namespace TransformStudios\Events\Types;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use RRule\RRule;
use RRule\RRuleInterface;
use RRule\RSet;
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

    protected function rule(bool $collapseDays = false): RRuleInterface
    {
        // if we're collapsing, then return an rrule instead of rset and use start of first day to end of last day
        if ($this->collapseMultiDays) {
            return new RRule([
                'count' => 1,
                'dtstart' => $this->end(),
                'freq' => RRule::DAILY,
            ]);
        }

        return tap(
            new RSet(),
            fn (RSet $rset) => $this->days->each(fn (Day $day) => $rset->addRRule([
                'count' => 1,
                'dtstart' => $day->end(),
                'freq' => RRule::DAILY,
            ]))
        );
    }

    protected function supplement(CarbonInterface $date): Entry
    {
        /** @var Day */
        $day = $this->days->first(fn (Day $day, int $index) => $this->collapseMultiDays ? $index == 0 : $date->isSameDay($day->start()));

        return tap(
            unserialize(serialize($this->event)),
            fn (Entry $occurrence) => $occurrence
                ->setSupplement('start', $day->start())
                ->setSupplement('end', $day->end())
                ->setSupplement('has_end_time', $day->hasEndTime())
        );
    }

    public function start(): CarbonImmutable
    {
        return $this->days->first()->start();
    }

    public function end(): CarbonImmutable
    {
        return $this->days->last()->end();
    }
}
