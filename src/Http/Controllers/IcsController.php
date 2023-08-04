<?php

namespace TransformStudios\Events\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event as ICalendarEvent;
use Statamic\Entries\Entry;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events;

class IcsController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public function __invoke(Request $request)
    {
        $handle = $request->get('collection', 'events');
        $date = $request->has('date') ? CarbonImmutable::parse($request->get('date')) : null;
        $eventId = $request->get('event');

        abort_if(! is_null($eventId) && is_null($entry = EntryFacade::find($eventId)), 404);

        if ($date && $entry) {
            $event = EventFactory::createFromEntry($entry);
            abort_unless($iCalendarEvent = $event->toICalendarEvent($date), 404);

            return $this->downloadIcs($iCalendarEvent, $event->title);
        }

        if ($date) {
            $events = Events::fromCollection(handle: $handle)
                ->between($date->startOfDay(), $date->endOfDay())
                ->map(fn (Entry $entry) => EventFactory::createFromEntry($entry)->toICalendarEvent($date))
                ->all();

            return $this->downloadIcs($events);
        }

        if ($entry) {
            return $this->downloadIcs(
                EventFactory::createFromEntry($entry)->toICalendarEvents()
            );
        }
    }

    private function downloadIcs(ICalendarEvent|array $event, string $title = 'events')
    {
        return response()->streamDownload(
            function () use ($event) {
                echo Calendar::create()->event(Arr::wrap($event))->get();
            },
            Str::slugify($title).'.ics',
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
            ]
        );
    }
}
