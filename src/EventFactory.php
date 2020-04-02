<?php

namespace Statamic\Addons\Events;

use Statamic\API\Arr;
use Statamic\Addons\Events\Types\MultiDayEvent;
use Statamic\Addons\Events\Types\RecurringEvent;
use Statamic\Addons\Events\Types\SingleDayEvent;

class EventFactory
{
    public static function createFromArray($data)
    {
        if (Arr::get($data, 'multi_day', false)) {
            return new MultiDayEvent($data);
        }

        // Statamic can save the recurrence "none" as "false" so we need to check for that
        if (bool(Arr::get($data, 'recurrence', false))) {
            return new RecurringEvent($data);
        }

        return new SingleDayEvent($data);
    }
}
