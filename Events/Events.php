<?php

namespace Statamic\Addons\Events;

use Illuminate\Support\Collection;
use Statamic\Addons\Events\Types\Event;

class Events
{
    /** @var Collection */
    private $events;

    public function __construct()
    {
        $this->events = collect();
    }

    public function add(Event $event)
    {
        $this->events->push($event);
    }

    public function upcoming($limit = 1, $offset = 0)
    {
        $events = $this->events->flatMap(function ($event, $ignore) use ($limit, $offset) {
            $days = $event->upcomingDates($limit, $offset);

            return $days->map(function ($day, $ignore) use ($event) {
                $event = clone $event;
                $event->start_date = $day->start()->toDateString();
                $event->start_time = $day->start()->toTimeString();
                $event->end_time = $day->end()->toTimeString();

                return $event;
            });
        })->filter()
        ->sortBy(function ($event, $ignore) {
            return carbon($event->start_date)->setTimeFromTimeString($event->startTime());
        })->values()
        ->take($limit);

        if ($limit === 1) {
            return $events->first();
        }

        return $events;
    }

    public function all($from, $to)
    {
        return $this->events->flatMap(function ($event, $ignore) use ($from, $to) {
            $days = $event->datesBetween($from, $to);

            return $days->map(function ($day, $ignore) use ($event) {
                $event = clone $event;
                $event->start_date = $day->start()->toDateString();
                $event->start_time = $day->start()->toTimeString();
                $event->end_time = $day->end()->toTimeString();

                return $event;
            });
        })->filter()
        ->sortBy(function ($event, $ignore) {
            return carbon($event->start_date)->setTimeFromTimeString($event->startTime());
        })->values();
    }
}
