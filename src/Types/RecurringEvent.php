<?php

namespace TransformStudios\Events\Types;

use Illuminate\Support\Carbon;
use RRule\RRule;
use RRule\RRuleInterface;

class RecurringEvent extends Event
{
    protected function rule(): RRuleInterface
    {
        $rule = [
            'dtstart' => $this->start()->setTimeFromTimeString($this->endTime()),
            'freq' => $this->frequency(),
            'interval' => $this->interval ?? 1,
        ];

        if ($end = $this->end_date) {
            $rule['until'] = Carbon::parse($end)->endOfDay();
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
            'every' => $this->periodToFrequency(),
            default => RRule::DAILY
        };
    }

    private function periodToFrequency(): int
    {
        return match ($this->period) {
            'days' => RRule::DAILY,
            'weeks' => RRule::WEEKLY,
            'months' => RRule::MONTHLY,
            'years' => RRule::YEARLY,
            default => RRule::DAILY
        };
    }
}
