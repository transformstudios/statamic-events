<?php

namespace TransformStudios\Events\Modifiers;

use Carbon\CarbonImmutable;
use Statamic\Modifiers\Modifier;
use Statamic\Support\Arr;

class InMonth extends Modifier
{
    public function index($value, $params, $context): bool
    {
        $month = $params[0] ?? Arr::get($context, 'get.month') ?? CarbonImmutable::now()->englishMonth;
        $year = $params[1] ?? Arr::get($context, 'get.year') ?? CarbonImmutable::now()->year;

        return CarbonImmutable::parse($value)->month === parse_date("$month $year")->month;
    }
}
