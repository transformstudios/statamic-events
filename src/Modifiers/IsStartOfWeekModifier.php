<?php

namespace Statamic\Addons\Events\Modifiers;

use Statamic\Extend\Modifier;

class IsStartOfWeekModifier extends Modifier
{
    public function index($value, $params, $context)
    {
        return Carbon::parse($value)->isSunday();
    }
}
