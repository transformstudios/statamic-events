<?php

namespace Statamic\Addons\Events;

use Carbon\Carbon;
use Statamic\API\Entry;
use Illuminate\Http\Request;
use Statamic\Extend\Controller;

class EventsController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);
    }

    public function getCalendar(Request $request)
    {
        $calendar = new Calendar($this->getConfig('events_collection'));

        $dates = $calendar->month('january', '2020');

        dd($dates);
    }

    /**
     * Get the next function
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
