<?php

namespace TransformStudios\Events\Types;

use RRule\RRule;
use RRule\RRuleInterface;
use RRule\RSet;

class SingleDayEvent extends Event
{
    protected function rule(): RRuleInterface
    {
        if ($this->spansDays()) {
            $rset = new RSet;
            $rset->addRRule([
                'count' => 1,
                'dtstart' => $this->start(),
                'freq' => RRule::DAILY,
            ]);
            $rset->addRRule([
                'count' => 1,
                'dtstart' => $this->end(),
                'freq' => RRule::DAILY,
            ]);

            return $rset;
        }

        return new RRule([
            'count' => 1,
            'dtstart' => $this->end(),
            'freq' => RRule::DAILY,
        ]);

    }

    private function spansDays(): bool
    {
        return $this->start()->setTimezone('UTC')->day != $this->end()->setTimezone('UTC')->day;
    }
}
