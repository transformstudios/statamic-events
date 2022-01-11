<?php

namespace TransformStudios\Events;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use TransformStudios\Events\Types\Event;

class Events
{
    private Collection $events;

    public function __construct()
    {
        $this->events = collect();
    }

    public function add(Event $event): void
    {
        $this->events->push($event);
    }

    public function upcoming(int $limit = 1, int $offset = 0): Collection
    {
        return $this->events->flatMap(
            fn (Event $event, bool $ignore) => $event
                ->upcomingDates($limit * ($offset + 1))
                ->filter()
                ->map(function (Day $day, bool $ignore) use ($event) {
                    $event = clone $event;
                    $event->has_end_time = $day->hasEndTime();
                    $event->start_date = $day->startDate();
                    $event->start_time = $day->startTime();

                    $event->end_date = $day->endDate();
                    $event->end_time = $day->endTime();
                    $event->start = $day->start();
                    $event->end = $day->end();

                    return $event;
                })
        )
        ->filter()
        ->sortBy(fn ($event, $ignore) => Carbon::parse($event->start_date)->setTimeFromTimeString($event->startTime()))
        ->values()
        ->splice($offset, $limit);
    }

    public function all(Carbon|string $from, Carbon|string $to): Collection
    {
        return $this->events->flatMap(function (Event $event, $ignore) use ($from, $to) {
            $days = $event->datesBetween($from, $to);

            return $days->map(function ($day, $ignore) use ($event) {
                $event = clone $event;
                $event->has_end_time = $day->hasEndTime();
                $event->start_date = $day->start()->toDateString();
                $event->start_time = $day->startTime();
                $event->end_time = $day->endTime();
                $event->start = $day->start();
                $event->end = $day->end();

                return $event;
            });
        })->filter()
        ->sortBy(fn ($event, $ignore) => $event->start())
        ->values();
    }

    public function count(): int
    {
        return $this->events->count();
    }
}
