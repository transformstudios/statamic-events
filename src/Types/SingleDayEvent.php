<?php

namespace TransformStudios\Events\Types;

use Carbon\CarbonImmutable;
use RRule\RRule;
use RRule\RRuleInterface;

class SingleDayEvent extends Event
{
    public function end(): ?CarbonImmutable
    {
        $date = CarbonImmutable::parse($this->start_date);

        if ($this->hasEndTime()) {
            return $date->setTimeFromTimeString($this->endTime());
        }

        return CarbonImmutable::parse($this->start_date)->endOfDay();
    }

    protected function rule(): RRuleInterface
    {
        return new RRule([
            'count' => 1,
            'dtstart' => $this->start()->setTimeFromTimeString($this->endTime()),
            'freq' => RRule::DAILY,
        ]);
    }
}
