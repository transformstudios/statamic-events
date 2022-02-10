<?php

namespace TransformStudios\Events;

use Illuminate\Support\Carbon;
use Statamic\Data\ContainsData;
use Statamic\Support\Arr;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class Occurrence
{
    use ContainsData, FluentlyGetsAndSets;

    public function __construct(array $data, Carbon $start, ?Carbon $end = null)
    {
        $this->merge($data);
        $this->start($start);
        $this->end($end);
    }

    public function end(string|Carbon $date = null): Carbon|self|null
    {
        return $this
            ->fluentlyGetOrSet('end')
            ->getter(fn ($end) => $end ? Carbon::parse($end) : null)
            ->args(func_get_args());
    }

    public function isAllDay(): bool|self|null
    {
        return $this
            ->fluentlyGetOrSet('all_day')
            ->args(func_get_args());
    }

    public function start(): ?Carbon
    {
        return Arr::get($this->data, 'start');
    }
}
