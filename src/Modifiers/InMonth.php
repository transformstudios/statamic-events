<?php

namespace TransformStudios\Events\Modifiers;

use Illuminate\Support\Carbon;
use Statamic\Modifiers\Modifier;
use Statamic\Support\Arr;

class InMonth extends Modifier
{
    public function index($value, $params, $context)
    {
        $month = Carbon::parse(
            Arr::get($context, 'get.month', Carbon::now()->englishMonth).
            ' '.
            Arr::get($context, 'get.year', Carbon::now()->year)
        )->month;

        return Carbon::parse($value)->month == $month;
    }
}
