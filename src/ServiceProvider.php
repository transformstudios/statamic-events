<?php

namespace TransformStudios\Events;

use Composer\InstalledVersions;
use Edalzell\Forma\Forma;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Fields\Field;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;
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
    }

    public function bootAddon()
    {
        $this
            ->bootCarbon()
            ->bootFields()
            ->publishConfig();
    }

    private function bootCarbon(): self
    {
        Carbon::setLocale(Site::current()->locale());

        if (InstalledVersions::getVersion('nesbot/carbon') >= '3') {
            return $this;
        }

        /*
         Using these deprecated methods because I couldn't figure out another way to
         have the weekstart set based on the current locale.

         When the next version of Carbon is released, it should be set properly: https://github.com/briannesbitt/Carbon/issues/2539#issuecomment-1037257768

        */

        if (is_string($weekStartDay = Carbon::getTranslator()->trans(id: 'first_day_of_week', locale: Site::current()->locale()))) {
            $weekStartDay = 0;
        }

        Carbon::setWeekStartsAt(day: $weekStartDay);
        Carbon::setWeekEndsAt(day: ($weekStartDay + 6) % 7);

        return $this;
    }

    private function bootFields(): self
    {
        Collection::computed(config('events.collection', 'events'), 'timezone', function ($entry, $value) {
            $value ??= config('events.timezone', config('app.timezone'));

            if ($entry->blueprint()->fields()->get('timezone')?->fieldtype() instanceof Timezones) {
                return $value;
            }

            return (new Field('timezone', ['type' => 'timezones', 'max_items' => 1]))
                ->setValue($value)
                ->setParent($entry)
                ->augment()
                ->value();
        });

        return $this;
    }

    private function publishConfig(): self
    {
        Statamic::afterInstalled(function ($command) {
            Artisan::call('vendor:publish', ['--tag' => 'events-config', '--force' => false]);
        });

        return $this;
    }
}
