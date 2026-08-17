<?php

namespace TransformStudios\Events\Types;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
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

        if (! is_null($address = $this->event->address ?? $this->event->location)) {
            $iCalEvent->address($address);
        }

        if (! is_null($coords = $this->event->coordinates)) {
            $iCalEvent->coordinates($coords['latitude'], $coords['longitude']);
        }

        if (! is_null($description = $this->event->description)) {
            $iCalEvent->description($description);
        }

        if (! is_null($link = $this->eventUrl())) {
            $iCalEvent->url($link);
        }

        return [$iCalEvent];
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
