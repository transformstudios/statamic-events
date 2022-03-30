<?php

namespace TransformStudios\Events\Tags;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\CalendarLinks\Link;
use Statamic\Entries\Entry;
use Statamic\Entries\EntryCollection;
use Statamic\Support\Arr;
use Statamic\Tags\Concerns\OutputsItems;
use Statamic\Tags\Tags;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events as Generator;

class Events extends Tags
{
    use OutputsItems;

    public function between(): EntryCollection|array
    {
        return $this->output($this->generator()->between(
            from: Carbon::parse($this->params->get('from', now()))->startOfDay(),
            to: Carbon::parse($this->params->get('to'))->endOfDay()
        ));
    }

    public function calendar(): Collection
    {
        $month = $this->params->get('month', now()->englishMonth);
        $year = $this->params->get('year', now()->year);

        $from = Carbon::parse($month.' '.$year)->startOfMonth();
        $to = Carbon::parse($month.' '.$year)->endOfMonth();

        $occurrences = $this
            ->generator()
            ->between(from: $from, to: $to)
            ->groupBy(fn (Entry $occurrence) => $occurrence->start->toDateString())
            ->map(fn (EntryCollection $occurrences, string $date) => $this->day(date: $date, occurrences: $occurrences))
            ->values();

        return $this->output($this->makeEmptyDates(from: $from, to: $to)->merge($occurrences));
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
            from: now()->startOfDay(),
            to: now()->modify($this->params->get('next'))->endOfDay()
        ));
    }

    public function nowOrParam(): string
    {
        $monthYear = request('month', now()->englishMonth).' '.request('year', now()->year);

        $month = Carbon::parse($monthYear);

        if ($modify = $this->params->get('modify')) {
            $month->modify($modify);
        }

        return $month->format(format: $this->params->get('format'));
    }

    public function today(): EntryCollection|array
    {
        return $this->output($this->generator()->between(from: now()->startOfDay(), to: now()->endOfDay()));
    }

    public function upcoming(): EntryCollection|array
    {
        return $this->output($this->generator()->upcoming(limit: $this->params->int('limit')));
    }

    private function generator(): Generator
    {
        return Generator::fromCollection(handle: $this->params->get('collection'))
            ->when(
                value: $this->params->bool('paginate'),
                callback: fn (Generator $generator) =>  $generator->pagination(perPage: $this->params->int('per_page'))
            );
    }

    private function day(string $date, EntryCollection $occurrences): array
    {
        return [
            'date' => $date,
            'dates' => $occurrences,
            'occurrences' => $occurrences,
        ];
    }

    private function makeEmptyDates(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $dates = collect();
        $currentDay = $from->copy()->toMutable();

        foreach (range(0, Carbon::parse($to)->diffInDays($from)) as $ignore) {
            $date = $currentDay->toDateString();
            $dates->put($date, [
                'date' => $date,
                'no_results' => true,
                'no_occurrences' => true,
                'empty' => true,
            ]);
            $currentDay->addDay();
        }

        return $dates;
    }
}
