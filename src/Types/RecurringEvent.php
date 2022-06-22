<?php

namespace TransformStudios\Events\Types;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use RRule\RRule;
use RRule\RRuleInterface;
use Spatie\IcalendarGenerator\Components\Event as ICalendarEvent;
use Spatie\IcalendarGenerator\Enums\RecurrenceFrequency;
use Spatie\IcalendarGenerator\ValueObjects\RRule as ICalendarRule;

class RecurringEvent extends Event
{
    public function interval(): int
    {
        return $this->interval ?? 1;
    }

    /**
     * @return ICalendarEvent[]
     */
    public function toICalendarEvents(): array
    {
        return [
            ICalendarEvent::create($this->event->title)
                ->uniqueIdentifier($this->event->id())
                ->startsAt($this->start())
                ->endsAt($this->end())
                ->rrule($this->spatieRule()),
        ];
    }

    protected function rule(): RRuleInterface
    {
        $rule = [
            'dtstart' => $this->start()->setTimeFromTimeString($this->endTime()),
            'freq' => $this->frequency(),
            'interval' => $this->interval(),
        ];

        if ($end = $this->end_date) {
            $rule['until'] = Carbon::parse($end)->endOfDay();
        }

        return new RRule($rule);
    }

    private function end(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->start_date)
            ->setTimeFromTimeString($this->endTime());
    }

    private function frequency(): int
    {
        return match ($this->recurrence->value()) {
            'daily' => Rrule::DAILY,
            'weekly' => Rrule::WEEKLY,
            'monthly' => Rrule::MONTHLY,
            'yearly' => Rrule::YEARLY,
            'every' => $this->periodToFrequency(),
            default => Rrule::DAILY
        };
    }

    private function frequencyToRecurrence(): RecurrenceFrequency
    {
        return match ($this->frequency()) {
            Rrule::DAILY => RecurrenceFrequency::daily(),
            Rrule::WEEKLY => RecurrenceFrequency::weekly(),
            Rrule::MONTHLY => RecurrenceFrequency::monthly(),
            Rrule::YEARLY => RecurrenceFrequency::yearly(),
            default => RecurrenceFrequency::daily()
        };
    }

    private function periodToFrequency(): int
    {
        return match ($this->period->value()) {
            'days' => Rrule::DAILY,
            'weeks' => Rrule::WEEKLY,
            'months' => Rrule::MONTHLY,
            'years' => Rrule::YEARLY,
            default => Rrule::DAILY
        };
    }

    private function spatieRule(): ICalendarRule
    {
        $rule = ICalendarRule::frequency($this->frequencyToRecurrence())
            ->interval($this->interval());

        if ($end = $this->end_date) {
            $rule->until(Carbon::parse($end)->endOfDay());
        }

        return $rule;
    }
}
