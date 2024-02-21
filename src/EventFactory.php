<?php

namespace TransformStudios\Events;

use Statamic\Entries\Entry;
use TransformStudios\Events\Types\Event;
use TransformStudios\Events\Types\MultiDayEvent;
use TransformStudios\Events\Types\RecurringEvent;
use TransformStudios\Events\Types\SingleDayEvent;

class EventFactory
{
    public static function createFromEntry(Entry $event, bool $collapseMultiDays = false): Event
    {
        if ($event->multi_day || $event->recurrence->value() === 'multi_day') {
            return new MultiDayEvent($event, $collapseMultiDays);
        }

        // this has to be `->value` because `recurrence` returns a `LabeledValue`.
        if (in_array($event->recurrence->value(), ['daily', 'weekly', 'monthly', 'every'])) {
            return new RecurringEvent($event);
        }

        return new SingleDayEvent($event);
    }
}
