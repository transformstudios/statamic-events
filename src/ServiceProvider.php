<?php

namespace TransformStudios\Events;

use Edalzell\Forma\Forma;
use Illuminate\Support\Carbon;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Providers\AddonServiceProvider;
use TransformStudios\Events\Fieldtypes\Timezones;
use TransformStudios\Events\Modifiers\InMonth;
use TransformStudios\Events\Modifiers\IsEndOfWeek;
use TransformStudios\Events\Modifiers\IsStartOfWeek;
use TransformStudios\Events\Tags\Events;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [
        Timezones::class,
    ];

    protected $modifiers = [
        InMonth::class,
        IsEndOfWeek::class,
        IsStartOfWeek::class,
    ];

    protected $routes = [
        'actions' => __DIR__.'/../routes/actions.php',
    ];

    protected $tags = [
        Events::class,
    ];

    public function boot()
    {
        parent::boot();

        Forma::add('transformstudios/events');

        $this
            ->bootCarbon()
            ->bootFields();
    }

    private function bootCarbon(): self
    {
        Carbon::setLocale(Site::current()->locale());

        $weekStartDay = Carbon::getTranslator()->trans(id: 'first_day_of_week', locale: Site::current()->locale());

        /*
         Using these deprecated methods because I couldn't figure out another way to
         have the weekstart set based on the current locale.

         When the next version of Carbon is released, it should be set properly: https://github.com/briannesbitt/Carbon/issues/2539#issuecomment-1037257768

        */
        Carbon::setWeekStartsAt(day: $weekStartDay);
        Carbon::setWeekEndsAt(day: ($weekStartDay + 6) % 7);

        return $this;
    }

    private function bootFields(): self
    {
        Collection::computed('events', 'timezone', function ($entry, $value) {
            if ($value) {
                return $value;
            }

            $timezone = config('events.timezone', config('app.timezone'));

            return Blueprint::find('collections/events/event')
                ->field('timezone')
                ->setValue($timezone)
                ->augment()
                ->value()
                ->value();
        });

        return $this;
    }
}
