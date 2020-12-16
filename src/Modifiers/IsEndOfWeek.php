<?php

namespace TransformStudios\Events\Modifiers;

use Illuminate\Support\Carbon;
use Statamic\Modifiers\Modifier;

class IsEndOfWeek extends Modifier
{
    public function index($value, $params, $context)
    {
        return Carbon::parse($value)->isSaturday();
    }
}
