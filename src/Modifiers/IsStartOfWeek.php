<?php

namespace TransformStudios\Events\Modifiers;

use Carbon\CarbonImmutable;
use Statamic\Modifiers\Modifier;

class IsStartOfWeek extends Modifier
{
    public function index($value, $params, $context)
    {
        $date = CarbonImmutable::parse($value);

        $date->isSameDay($date->locale(CarbonImmutable::getLocale())->startOfWeek());

        return $date->dayOfWeek == now()->startOfWeek()->dayOfWeek;
    }
}
