<?php

namespace TransformStudios\Events;

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

    protected $routes = [
       'actions' => __DIR__.'/../routes/actions.php',
    ];

    protected $tags = [
        Events::class,
    ];
}
