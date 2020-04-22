<?php

namespace Statamic\Addons\Events\Modifiers;

use Carbon\Carbon;
use Statamic\Support\Arr;
use Statamic\Extend\Modifier;

class InMonthModifier extends Modifier
{
    public function index($value, $params, $context)
    {
        $date = Carbon::parse(
            Arr::get($context, 'get.month', Carbon::now()->englishMonth).
            ' '.
            Arr::get($context, 'get.year', Carbon::now()->year)
        );

        return Carbon::parse($value)->month == $date->month;
    }
}
