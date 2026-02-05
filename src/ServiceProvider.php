<?php

namespace TransformStudios\Events;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
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
        CarbonImmutable::setLocale(Site::current()->locale());

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
