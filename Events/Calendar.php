<?php

namespace Statamic\Addons\Events;

use Carbon\Carbon;
use Statamic\API\Entry as EntryAPI;
use Statamic\Addons\Events\EventFactory;

class Calendar
{
    /** @var Events */
    private $events;

    public function __construct(string $handle)
    {
        $this->events = new Events;

        EntryAPI::whereCollection($handle)
            ->removeUnpublished()
            ->each(function ($event) {
                return $this->events->add(EventFactory::createFromArray($event->toArray()));
            });
    }

    /**
     * @return array
     */
    public function month(string $month = null, string $year = null)
    {
        if (is_null($month)) {
            $month = Carbon::now()->englishMonth;
        }

        if (is_null($year)) {
            $year = Carbon::now()->year;
        }

        $carbonMonth = carbon($month . ' ' . $year);

        $from = $carbonMonth->copy()->startOfMonth()->startOfWeek();
        $to = $carbonMonth->copy()->endOfMonth()->endOfWeek();

        return array_merge(
            $this->makeEmptyDates($from, $to),
            $this->getDates($from, $to)->toArray()
        );
    }

    private function getDates($from, $to)
    {
        return $this->events
            ->all($from, $to)
            ->groupBy(function ($event, $key) {
                return $event->start_date;
            })
            ->map(function ($days, $key) {
                return [
                    'date' => $key,
                    'dates' => $days->toArray(),
                ];
            });
    }

    private function makeEmptyDates($from, $to): array
    {
        $dates = [];
        $currentDay = $from->copy();

        foreach (range(0, $to->diffInDays($from)) as $ignore) {
            $date = $currentDay->toDateString();
            $dates[$date] = [
                'date' => $date,
                'no_results' => true,
            ];
            $currentDay->addDay();
        }

        return $dates;
    }
}
