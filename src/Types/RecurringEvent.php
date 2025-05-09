<?php

namespace TransformStudios\Events\Types;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use RRule\RRule;
use RRule\RRuleInterface;
use Spatie\IcalendarGenerator\Components\Event as ICalendarEvent;
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
        $iCalEvent = ICalendarEvent::create($this->event->title)
            ->uniqueIdentifier($this->event->id())
            ->startsAt($this->start())
            ->endsAt($this->end())
            ->rrule($this->spatieRule());

        if (! is_null($location = $this->location($this->event))) {
            $iCalEvent->address($location);
        }

        if (! is_null($description = $this->event->description)) {
            $iCalEvent->description($description);
        }

        if (! is_null($link = $this->event->link)) {
            $iCalEvent->url($link);
        }

        return [$iCalEvent];
    }

    protected function rule(): RRuleInterface
    {
        $rule = [
            'dtstart' => $this->end(),
            'freq' => $this->frequency(),
            'interval' => $this->interval(),
        ];

        if ($end = $this->end_date) {
            $rule['until'] = Carbon::parse($end)->shiftTimezone($this->timezone['timezone'])->endOfDay();
        }

        if (! empty($days = $this->onSpecificDays())) {
            $rule['byday'] = Arr::pluck($days, 'rrule');
        }

        return new RRule($rule);
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
