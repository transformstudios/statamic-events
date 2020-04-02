<?php

namespace Statamic\Addons\Events\Modifiers;

use Statamic\Extend\Modifier;

class IsEndOfWeekModifier extends Modifier
{
    public function index($value, $params, $context)
    {
        return carbon($value)->isSaturday();
    }
}