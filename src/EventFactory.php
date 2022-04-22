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
        if ($event->multi_day) {
            return new MultiDayEvent(event: $event, collapseMultiDays: $collapseMultiDays);
        }

        // this has to be `->value` because `recurrence` returns a `LabeledValue`.
        if ($event->recurrence->value()) {
            return new RecurringEvent(event: $event);
        }

        return new SingleDayEvent(event: $event);
    }
}
