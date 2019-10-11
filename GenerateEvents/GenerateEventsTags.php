<?php

namespace Statamic\Addons\GenerateEvents;

use Carbon\Carbon;
use Statamic\API\Arr;
use Statamic\Extend\Tags;

class GenerateEventsTags extends Tags
{
    /**
     * The {{ generate_events }} tag
     *
     * @return string|array
     */
    public function nextOccurrence()
    {
        if ($recurrenceType = Arr::get($this->context, 'recurrence_type')) {
            $startDate = carbon(Arr::get($this->context, 'start_date'));
            $generator = new Generator(
                $startDate,
                $recurrenceType,
                Arr::get($this->context, 'recurrence_end_date')
            );

            $nextOccurrence = $generator->nextOccurrence(time());

            return $nextOccurrence;
        }
    }

    public function nextOccurrences()
    {
        if ($recurrenceType = Arr::get($this->context, 'recurrence_type')) {
            $generator = new Generator(
                Arr::get($this->context, 'start_date'),
                $recurrenceType,
                Arr::get($this->context, 'recurrence_end_date')
            );

            $nextOccurrences = $generator->nextOccurrences($this->getParamInt('number_of_occurrences', 1), Carbon::now());

            return $this->parseLoop(
                collect($nextOccurrences)
                    ->map(function ($occurrence, $key) {
                        return ['occurrence' => $occurrence];
                    })->all()
            );
        }
    }
}
