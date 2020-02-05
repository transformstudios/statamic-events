<?php

namespace Statamic\Addons\Events\Modifiers;

use Carbon\Carbon;
use Statamic\API\Arr;
use Statamic\Extend\Modifier;

class InMonthModifier extends Modifier
{
    public function index($value, $params, $context)
    {
        $date = carbon(
            Arr::get($context, 'get.month', Carbon::now()->englishMonth) .
            ' ' .
            Arr::get($context, 'get.year', Carbon::now()->year)
        );

        return carbon($value)->month == $date->month;
    }
}
