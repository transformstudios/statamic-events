<?php

namespace Statamic\Addons\Events\Modifiers;

use Carbon\Carbon;
use Statamic\Extend\Modifier;

class IsEndOfWeekModifier extends Modifier
{
    public function index($value, $params, $context)
    {
        return Carbon::parse($value)->isSaturday();
    }
}
