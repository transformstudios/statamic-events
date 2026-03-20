<?php

namespace TransformStudios\Events\Types;

use RRule\RRule;
use RRule\RRuleInterface;
use RRule\RSet;

class SingleDayEvent extends Event
{
    protected function rule(): RRuleInterface
    {
        $rset = tap(new RSet)->addRRule([
            'count' => 1,
            'dtstart' => $this->end(),
            'freq' => RRule::DAILY,
        ]);

        // if the occurrence spans days, include the start so that it's picked up on the "between" method
        if ($this->spansDays()) {
            $rset->addRRule([
                'count' => 1,
                'dtstart' => $this->start(),
                'freq' => RRule::DAILY,
            ]);
        }

        return $rset;
    }
}
