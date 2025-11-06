<?php

namespace TransformStudios\Events;

use Composer\InstalledVersions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Statamic\Entries\Entry;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Fields\Field;
use Statamic\Fields\Value;
use Statamic\Fieldtypes\Dictionary;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'actions' => __DIR__.'/../routes/actions.php',
    ];

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
        collect(Events::setting('collections', [['collection' => 'events']]))
            ->each(fn (array $collection) => $this
                ->defineComputedTimezoneField($collection['collection']));

        return $this;
    }

    private function defineComputedTimezoneField(string $handle): void
    {
        Collection::computed($handle, 'timezone', $this->timezone(...));
    }

    private function publishConfig(): self
    {
        Statamic::afterInstalled(function ($command) {
            Artisan::call('vendor:publish', ['--tag' => 'events-config', '--force' => false]);
        });

        return $this;
    }

    private function timezone(Entry $entry, $value): string|Value
    {
        $value ??= Events::timezone();

        if ($entry->blueprint()->fields()->get('timezone')?->fieldtype() instanceof Dictionary) {
            return $value;
        }

        return (new Field('timezone', ['type' => 'timezones', 'max_items' => 1]))
            ->setValue($value)
            ->setParent($entry)
            ->augment()
            ->value();
    }
}
