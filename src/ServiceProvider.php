<?php

namespace TransformStudios\Events;

use Illuminate\Support\Carbon;
use Statamic\Facades\Site;
use Statamic\Providers\AddonServiceProvider;
use TransformStudios\Events\Modifiers\InMonth;
use TransformStudios\Events\Modifiers\IsEndOfWeek;
use TransformStudios\Events\Modifiers\IsStartOfWeek;
use TransformStudios\Events\Tags\Events;

class ServiceProvider extends AddonServiceProvider
{
    protected $modifiers = [
        InMonth::class,
        IsEndOfWeek::class,
        IsStartOfWeek::class,
    ];

    protected $tags = [
        Events::class,
    ];

    public function boot()
    {
        parent::boot();

        $this->publishes([
            __DIR__.'/../resources/fieldsets' => resource_path('fieldsets'),
        ], 'events-fieldsets');

        // set weekstart/end
        $weekStartDay = Carbon::getTranslator()->trans(id: 'first_day_of_week', locale: Site::current()->locale());

        Carbon::setWeekStartsAt($weekStartDay);
        Carbon::setWeekEndsAt(($weekStartDay + 6) % 7);
    }
}
