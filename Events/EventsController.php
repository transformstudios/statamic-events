<?php

namespace Statamic\Addons\Events;

use Statamic\API\Entry;
use Illuminate\Http\Request;
use Statamic\Extend\Controller;

class EventsController extends Controller
{
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
                ->next(
                    $request->input('limit', 1),
                    $request->input('offset', 0)
                )
                ->toArray();
    }
}
