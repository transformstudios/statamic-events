<?php

namespace TransformStudios\Events\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Statamic\Facades\Entry as EntryFacade;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events;

class IcsController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __invoke(Request $request)
    {
        // several paths
        // 1, if there's a `date` and no `id` then create download for all occurrences on that date
        // ~~2, if there's a `date` and `id`, then create a download for a single event occurrence~~
        // 3, if there's only an `id`, then create a download for all occurrences of that event, using rrule

        // $generator = Events::fromCollection(handle: $request->get('collection', 'events'));

        // if () {
        //     $generator->event(id: $event);
        // }

        $date = $request->has('date') ? Carbon::parse($request->get('date')) : null;

        $event = $request->get('event');

        if ($date && $event) {
            if (! $iCalEvent = EventFactory::createFromEntry(EntryFacade::find($event))->toICalendarEvent($date)) {
                throw ValidationException::withMessages(['event_date' => 'Event does not occur on '.$date->toDateString()]);
            }

            return response()->streamDownload(
                function () use ($iCalEvent) {
                    echo Calendar::create()->event($iCalEvent)->get();
                },
                'my-awesome-calendar.ics',
                [
                    'Content-Type' => 'text/calendar; charset=utf-8',
                ]
            );
        }
    }
}
