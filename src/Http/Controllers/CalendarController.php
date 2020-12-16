<?php

namespace TransformStudios\Events\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Statamic\Addons\Events\Calendar;
use Statamic\API\Entry;
use Statamic\Http\Controllers\Controller;

class CalendarController extends Controller
{
    public function __construct()
    {
        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);
    }

    public function getCalendar(Request $request)
    {
        $calendar = new Calendar($this->getConfig('events_collection'));

        return $calendar->month($request->month, $request->year);
    }

    /**
     * Get the next function.
     *
     * @return array
     */
    public function getNext(Request $request)
    {
        $this->validate($request, [
            'collection' => 'required',
            'limit' => 'sometimes|required|integer',
            'offset' => 'sometimes|required|integer',
        ]);

        $events = new Events();

        Entry::whereCollection($request->input('collection'))->each(function ($event) use ($events) {
            $events->add($event->toArray());
        });

        return $events
                ->upcoming(
                    $request->input('limit', 1),
                    $request->input('offset', 0)
                )
                ->toArray();
    }
}
