<?php

namespace TransformStudios\Events\Types;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use RRule\RRule;
use RRule\RRuleInterface;
use Spatie\IcalendarGenerator\Enums\RecurrenceFrequency;
use Spatie\IcalendarGenerator\ValueObjects\RRule as ICalendarRule;

class RecurringEvent extends Event
{
    public function onSpecificDays(): array
    {
        return $this->specific_days ?? [];
    }

    public function interval(): int
    {
        return $this->interval ?? 1;
    }

    /**
     * @return ICalendarEvent[]
     */
    public function toICalendarEvents(): array
    {
        if (! $event = $this->toICalendarEvent($this->start())) {
            return [];
        }

        return [$event->rrule($this->spatieRule())];
    }

    protected function rule(bool $useEnd = false): RRuleInterface
    {
        $rule = [
            'dtstart' => $useEnd ? $this->end() : $this->start(),
            'freq' => $this->frequency(),
            'interval' => $this->interval(),
        ];

        if ($end = $this->end_date) {
            $rule['until'] = CarbonImmutable::parse($end)->shiftTimezone($this->timezoneName())->endOfDay();
        }

        if (! empty($days = $this->onSpecificDays())) {
            $rule['byday'] = Arr::pluck($days, 'rrule');
        }

        return new RRule($rule);
    }

    private function frequency(): int
    {
        return match ($this->recurrence->value()) {
            'daily' => RRule::DAILY,
            'weekly' => RRule::WEEKLY,
            'monthly' => RRule::MONTHLY,
            'yearly' => RRule::YEARLY,
            'every' => $this->periodToFrequency(),
            default => RRule::DAILY
        };
    }

    private function frequencyToRecurrence(): RecurrenceFrequency
    {
        return match ($this->frequency()) {
            RRule::DAILY => RecurrenceFrequency::Daily,
            RRule::WEEKLY => RecurrenceFrequency::Weekly,
            RRule::MONTHLY => RecurrenceFrequency::Monthly,
            RRule::YEARLY => RecurrenceFrequency::Yearly,
            default => RecurrenceFrequency::Daily
        };
    }

    private function periodToFrequency(): int
    {
        return match ($this->period->value()) {
            'days' => RRule::DAILY,
            'weeks' => RRule::WEEKLY,
            'months' => RRule::MONTHLY,
            'years' => RRule::YEARLY,
            default => RRule::DAILY
        };
    }

    private function spatieRule(): ICalendarRule
    {
        $rule = ICalendarRule::frequency($this->frequencyToRecurrence())
            ->interval($this->interval());

        if ($end = $this->end_date) {
            $rule->until(CarbonImmutable::parse($end)->endOfDay());
        }

        return $rule;
    }
}
