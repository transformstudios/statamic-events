<?php

namespace TransformStudios\Events;

use Statamic\Entries\Entry;
use TransformStudios\Events\Types\Event;
use TransformStudios\Events\Types\MultiDayEvent;
use TransformStudios\Events\Types\RecurringEvent;
use TransformStudios\Events\Types\SingleDayEvent;

class EventFactory
{
    public static function createFromEntry(Entry $entry): Event
    {
        if ($entry->value('multi_day')) {
            return new MultiDayEvent($entry);
        }

        if ($entry->value('recurrence')) {
            return new RecurringEvent($entry);
        }

        return new SingleDayEvent($entry);
    }
}
