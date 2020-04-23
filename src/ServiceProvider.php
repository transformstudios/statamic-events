<?php

namespace TransformStudios\Events;

use TransformStudios\Events\Tags\Events;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
       'actions' => __DIR__.'/../routes/actions.php',
    ];

    protected $tags = [
        Events::class,
    ];

    public function boot()
    {
        parent::boot();

        $this->bootConfig();
    }

    private function bootConfig()
    {
        $this->publishes([
            __DIR__.'../config/events.php' => config_path('events.php'),
        ]);
    }
}
