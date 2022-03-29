<?php

namespace TransformStudios\Events\Tags;

use Illuminate\Support\Carbon;
use Spatie\CalendarLinks\Link;
use Statamic\Entries\EntryCollection;
use Statamic\Support\Arr;
use Statamic\Tags\Concerns\OutputsItems;
use Statamic\Tags\Tags;
use TransformStudios\Events\Calendar;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events as Generator;

class Events extends Tags
{
    use OutputsItems;

    public function between(): EntryCollection|array
    {
        return $this->output($this->generator()->between(
            Carbon::parse($this->params->get('from', now()))->startOfDay(),
            Carbon::parse($this->params->get('to'))->endOfDay()
        ));
    }

    public function calendar(): array
    {
        $calendar = new Calendar($this->params->get('collection', config('events.events_collection')));

        return array_values($calendar->month($this->params->get('month'), $this->params->get('year')));
    }

    public function downloadLink(): string
    {
        $event = EventFactory::createFromArray($this->context);

        $from = $event->start();
        $to = $event->end();

        if ($event->isRecurring()) {
            $from->setDateFrom(Carbon::parse($this->params->get('date')));
            $to = $from->copy()->setTimeFromTimeString($event->endTime());
        }

        $title = Arr::get($this->context, 'title');
        $location = Arr::get($this->context, 'location', '');

        $type = $this->params->get('type', 'ics');

        $link = Link::create($title, $from, $to, $event->isAllDay())->address($location);

        return $link->$type();
    }

    public function in(): EntryCollection|array
    {
        return $this->output($this->generator()->between(
            Carbon::now()->startOfDay(),
            Carbon::now()->modify($this->params->get('next'))->endOfDay()
        ));

        // $this->loadEvents($this->params->bool('collapse_multi_days', false));
    }

    public function nowOrParam(): string
    {
        $monthYear = request('month', Carbon::now()->englishMonth).' '.request('year', Carbon::now()->year);

        $month = Carbon::parse($monthYear);

        if ($modify = $this->params->get('modify')) {
            $month->modify($modify);
        }

        return $month->format($this->params->get('format'));
    }

    public function today(): EntryCollection|array
    {
        // $this->loadEvents($this->params->bool('collapse_multi_days', false));

        return $this->output($this->generator()->between(
            Carbon::now()->startOfDay(),
            Carbon::now()->endOfDay()
        ));
    }

    public function upcoming(): EntryCollection|array
    {
        return $this->output($this->generator()->upcoming($this->params->int('limit')));
    }

    private function generator(): Generator
    {
        $generator = Generator::fromCollection(handle: $this->params->get('collection'));

        if ($this->params->bool('paginate')) {
            $generator->pagination(perPage: $this->params->int('per_page'));
        }

        return $generator;
    }

    private function makeEmptyDates(Carbon| string $from, Carbon|string $to): array
    {
        $dates = [];
        $currentDay = $from = Carbon::parse($from);

        foreach (range(0, Carbon::parse($to)->diffInDays($from)) as $ignore) {
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
