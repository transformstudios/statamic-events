<?php

namespace TransformStudios\Events\Modifiers;

use Carbon\CarbonImmutable;
use Statamic\Modifiers\Modifier;
use Statamic\Support\Arr;

class InMonth extends Modifier
{
    public function index($value, $params, $context)
    {
        $month = parse_date(
            Arr::get($context, 'get.month', CarbonImmutable::now()->englishMonth).
            ' '.
            Arr::get($context, 'get.year', CarbonImmutable::now()->year)
        )->month;

        return CarbonImmutable::parse($value)->month == $month;
    }
}
