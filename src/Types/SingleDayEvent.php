<?php

namespace TransformStudios\Events\Types;

use RRule\RRule;
use RRule\RRuleInterface;

class SingleDayEvent extends Event
{
    protected function rule(): RRuleInterface
    {
        return new RRule([
            'count' => 1,
            'dtstart' => $this->start(),
            'freq' => RRule::DAILY,
        ]);
    }
}
