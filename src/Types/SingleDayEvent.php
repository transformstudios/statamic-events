<?php

namespace TransformStudios\Events\Types;

use RRule\RRule;
use RRule\RRuleInterface;

class SingleDayEvent extends Event
{
    protected function rule(bool $useEnd = false): RRuleInterface
    {
        if ($useEnd) {
            return new RRule([
                'count' => 1,
                'dtstart' => $this->end(),
                'freq' => RRule::DAILY,
            ]);
        }

        return new RRule([
            // 'count' => 1,
            'dtstart' => $this->start(),
            'until' => $this->end(),
            'freq' => RRule::DAILY,
        ]);
    }
}
