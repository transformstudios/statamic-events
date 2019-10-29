<?php

namespace Statamic\Addons\GenerateEvents;

use Statamic\API\Entry;
use Illuminate\Http\Request;
use Statamic\Extend\Controller;

class GenerateEventsController extends Controller
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

        $generator = new Generator();

        Entry::whereCollection($request->input('collection'))->each(function ($event) use ($generator) {
            $generator->add($event->toArray());
        });

        return $generator
                ->nextXOccurrences(
                    $request->input('limit', 1),
                    $request->input('offset', 1)
                )
                ->toArray();
    }
}
