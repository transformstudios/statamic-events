<?php

namespace TransformStudios\Events;

use Statamic\Entries\Entry;
use TransformStudios\Events\Types\Event;
use TransformStudios\Events\Types\MultiDayEvent;
use TransformStudios\Events\Types\RecurringEvent;
use TransformStudios\Events\Types\SingleDayEvent;

class EventFactory
{
    public static function createFromEntry(Entry $event): Event
    {
        if ($event->value('multi_day')) {
            return new MultiDayEvent($event);
        }

        if ($event->value('recurrence')) {
            return new RecurringEvent($event);
        }

        return new SingleDayEvent($event);
    }
}
