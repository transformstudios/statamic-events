<?php

namespace TransformStudios\Events\Types;

use RRule\RRule;
use RRule\RRuleInterface;

class RecurringEvent extends Event
{
    protected function rule(): RRuleInterface
    {
        $rule = [
            'dtstart' => $this->start(),
            'freq' => $this->frequency(),
        ];

        if ($end = $this->end()) {
            $rule['until'] = $end->endOfDay();
        }

        return new RRule($rule);
    }

    private function frequency(): int
    {
        return match ($this->recurrence) {
            'daily' => RRule::DAILY,
            'weekly' => RRule::WEEKLY,
            'monthly' => RRule::MONTHLY,
            'yearly' => RRule::YEARLY,
            default => RRule::DAILY
        };
    }
}
