<?php

namespace TransformStudios\Events\Modifiers;

use Carbon\CarbonImmutable;
use Statamic\Facades\Site;
use Statamic\Modifiers\Modifier;

class IsEndOfWeek extends Modifier
{
    public function index($value, $params, $context)
    {
        /*
            have to do this because Statamic sets the Carbon locale
            to the `lang` of the site, instead of the `locale`
        */
        $currentLocale = CarbonImmutable::getLocale();
        CarbonImmutable::setLocale(Site::current()->locale());

        $date = CarbonImmutable::parse($value);

        $isStartOfWeek = $date->dayOfWeek == now()->endOfWeek()->dayOfWeek;

        CarbonImmutable::setLocale($currentLocale);

        return $isStartOfWeek;
    }
}
