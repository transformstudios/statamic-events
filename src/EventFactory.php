<?php

namespace TransformStudios\Events;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use TransformStudios\Events\Types\MultiDayEvent;
use TransformStudios\Events\Types\RecurringEvent;
use TransformStudios\Events\Types\SingleDayEvent;

class EventFactory
{
    public static function createFromArray($data)
    {
        if (Arr::get($data, 'multi_day', false)) {
            return new MultiDayEvent($data);
        }

        // Statamic can save the recurrence "none" as "false" so we need to check for that
        if (Str::toBool(Arr::get($data, 'recurrence', false))) {
            return new RecurringEvent($data);
        }

        return new SingleDayEvent($data);
    }
}
