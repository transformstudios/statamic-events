<?php

namespace TransformStudios\Events\Types;

use Carbon\CarbonImmutable;
use RRule\RRule;
use RRule\RRuleInterface;

class RecurringEvent extends Event
{
    public function end(): ?CarbonImmutable
    {
        if (! $endDate = $this->end_date) {
            return null;
        }

        return CarbonImmutable::parse($endDate)->setTimeFromTimeString($this->endTime());
    }

    protected function rule(): RRuleInterface
    {
        $rule = [
            'dtstart' => $this->start()->setTimeFromTimeString($this->endTime()),
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
