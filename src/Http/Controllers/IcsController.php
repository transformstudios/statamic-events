<?php

namespace TransformStudios\Events\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event as ICalendarEvent;
use Statamic\Entries\Entry;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Support\Arr;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events;

class IcsController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __invoke(Request $request)
    {
        $handle = $request->get('collection', 'events');
        $date = $request->has('date') ? CarbonImmutable::parse($request->get('date')) : null;
        $eventId = $request->get('event');

        if ($date && $eventId) {
            throw_unless(
                $event = EventFactory::createFromEntry(EntryFacade::find($eventId))->toICalendarEvent($date),
                ValidationException::withMessages(['event_date' => 'Event does not occur on '.$date->toDateString()])
            );

            return $this->downloadIcs($event);
        }

        if ($date) {
            $events = Events::fromCollection(handle: $handle)
                ->between($date->startOfDay()->setMicrosecond(0), $date->endOfDay()->setMicrosecond(0))
                ->map(fn (Entry $entry) => EventFactory::createFromEntry($entry)->toICalendarEvent($date))
                ->all();

            return $this->downloadIcs($events);
        }

        if ($eventId) {
            return $this->downloadIcs(
                EventFactory::createFromEntry(EntryFacade::find($eventId))->toICalendarEvents()
            );
        }
    }

    private function downloadIcs(ICalendarEvent|array $event)
    {
        return response()->streamDownload(
            function () use ($event) {
                echo Calendar::create()
                ->event(Arr::wrap($event))
                ->get();
            },
            'my-awesome-calendar.ics',
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
            ]
        );
    }
}
