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

        if ($type = Arr::get($data, 'recurrence', false)) {
            return $type === 'every' ? new EveryX($data) : new RecurringEvent($data);
        }

        return new SingleDayEvent($data);
    }
}
