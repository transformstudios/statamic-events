<?php

namespace TransformStudios\Events\Modifiers;

use Carbon\CarbonImmutable;
use Composer\InstalledVersions;
use Statamic\Modifiers\Modifier;

class IsStartOfWeek extends Modifier
{
    public function index($value, $params, $context)
    {
        $date = CarbonImmutable::parse($value);

        if (InstalledVersions::getVersion('nesbot/carbon') >= '3') {
            $date->isSameDay($date->locale(CarbonImmutable::getLocale())->startOfWeek());
        }

        return $date->dayOfWeek == now()->startOfWeek()->dayOfWeek;
    }
}
