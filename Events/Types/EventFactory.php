<?php

namespace Statamic\Addons\Events\Types;

use Statamic\API\Arr;
use Statamic\Addons\Events\Types\Recurring\EveryX;

class EventFactory
{
    public static function createFromArray($data)
    {
        if (Arr::get($data, 'multi_day', false)) {
            return new MultiDayEvent($data);
        }

        $type = Arr::get($data, 'recurrence', false);

        // Statamic can save the recurrence "none" as "false" so we need to check for that
        if (bool($type)) {
            return $type === 'every' ? new EveryX($data) : new RecurringEvent($data);
        }

        return new SingleDayEvent($data);
    }
}
