<?php

namespace TransformStudios\Events\Actions;

use Spatie\IcalendarGenerator\Components\Event;
use TransformStudios\Events\Data\ICalEvent;

class GenerateICalendar
{
    public static function execute(ICalEvent $event): Event
    {
        return Event::create($event->title)
            ->withoutTimezone()
            ->uniqueIdentifier($event->id)
            ->startsAt($event->start)
            ->endsAt($event->end)
            ->address($event->address)
            ->description($event->description)
            ->url($event->url);
    }
}
