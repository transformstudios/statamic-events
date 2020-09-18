<?php

namespace TransformStudios\Events;

use Statamic\Providers\AddonServiceProvider;
use TransformStudios\Events\Tags\Events;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
       'actions' => __DIR__.'/../routes/actions.php',
    ];

    protected $tags = [
        Events::class,
    ];
}
