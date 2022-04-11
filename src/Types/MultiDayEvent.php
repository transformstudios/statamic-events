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

    public function __construct(Entry $event)
    {
        parent::__construct($event);

        $this->days = collect($this->event->days)
            ->map(fn (Values $day) => new Day($day->all(), $this->isAllDay()));
    }

    protected function rule(): RRuleInterface
    {
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
        $day = $this->days->first(fn (Day $day) => $date->isSameDay($day->start()));

        return tap(
            unserialize(serialize($this->event)),
            fn (Entry $occurrence) => $occurrence
                ->setSupplement('start', $day->start())
                ->setSupplement('end', $day->end())
        );
    }

    public function start(): CarbonImmutable
    {
        return $this->days->first()->start();
    }
}
