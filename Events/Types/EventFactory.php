<?php

namespace Statamic\Addons\Events\Types;

use Statamic\API\Arr;

class EventFactory
{
    public static function createFromArray($data)
    {
        if (Arr::get($data, 'multi_day', false)) {
            return new MultiDayEvent($data);
        }

        if (Arr::get($data, 'recurrence', false)) {
            return new RecurringEvent($data);
        }

        return new SingleDayEvent($data);
    }
}
