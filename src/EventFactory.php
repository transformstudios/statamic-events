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
        $eventType = static::getTypeClass($event);

        return new $eventType($event, $collapseMultiDays);
    }

    public static function getTypeClass(Entry $event): string
    {
        if (in_array($event->get('recurrence'), ['daily', 'weekly', 'monthly', 'every'])) {
            return RecurringEvent::class;
        }

        if (($event->multi_day || $event->get('recurrence') === 'multi_day') && !empty($event->get('days'))) {
            return MultiDayEvent::class;
        }

        return SingleDayEvent::class;
    }
}
