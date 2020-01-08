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

    /**
     * @return Collection|Event
     */
    public function upcoming(int $limit = 1, int $offset = 0)
    {
        $events = $this->events->flatMap(function ($event, $ignore) use ($limit, $offset) {
            return $event
                ->upcomingDates($limit * ($offset + 1))
                ->filter()
                ->map(function ($day, $ignore) use ($event) {
                    $event = clone $event;
                    $event->start_date = $day->startDate();
                    $event->start_time = $day->startTime();

                    $event->end_date = $day->endDate();
                    $event->end_time = $day->endTime();

                    return $event;
                });
        })->filter()
        ->sortBy(function ($event, $ignore) {
            return carbon($event->start_date)->setTimeFromTimeString($event->startTime());
        })->values()
        ->splice($offset, $limit);

        return $limit === 1 ? $events->first() : $events;
    }

    public function all($from, $to)
    {
        return $this->events->flatMap(function ($event, $ignore) use ($from, $to) {
            $days = $event->datesBetween($from, $to);

            return $days->map(function ($day, $ignore) use ($event) {
                $event = clone $event;
                $event->start_date = $day->start()->toDateString();
                $event->start_time = $day->startTime();
                $event->end_time = $day->endTime();

                return $event;
            });
        })->filter()
        ->sortBy(function ($event, $ignore) {
            return carbon($event->start_date)->setTimeFromTimeString($event->startTime());
        })->values();
    }
}
