<?php

namespace TransformStudios\Events\Tags;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Statamic\Contracts\Query\Builder;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Entries\Entry;
use Statamic\Entries\EntryCollection;
use Statamic\Facades\Compare;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Tags\Concerns\OutputsItems;
use Statamic\Tags\Tags;
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

        $from = Carbon::parse($month.' '.$year)->startOfMonth()->startOfWeek();
        $to = Carbon::parse($month.' '.$year)->endOfMonth()->endOfWeek();

        $occurrences = $this
            ->generator()
            ->between(from: $from, to: $to)
            ->groupBy(fn (Entry $occurrence) => $occurrence->start->toDateString())
            ->map(fn (EntryCollection $occurrences, string $date) => $this->day(date: $date, occurrences: $occurrences));

        return $this->output($this->makeEmptyDates(from: $from, to: $to)->merge($occurrences)->values());
    }

    public function downloadLink(): string
    {
        return route(
            'statamic.events.ics.show',
            Arr::removeNullValues([
                'collection' => $this->params->get('collection', 'events'),
                'date' => $this->params->has('date') ? Carbon::parse($this->params->get('date'))->toDateString() : null,
                'event' => $this->params->get('event'),
            ])
        );
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
        return $this->output(
            $this
                ->generator()
                ->between(
                    from: $this->params->bool(['ignore_finished', 'ignore_past']) ? now() : now()->startOfDay(),
                    to: now()->endOfDay()
                )
        );
    }

    public function upcoming(): EntryCollection|array
    {
        $limit = $this->params->int('limit');
        $occurrences = $this->generator()->upcoming($limit);

        if ($this->params->has('paginate')) {
            return $this->output($occurrences);
        }

        return $this->output($occurrences->take($limit));
    }

    private function day(string $date, EntryCollection $occurrences): array
    {
        return [
            'date' => $date,
            'dates' => $occurrences,
            'occurrences' => $occurrences,
        ];
    }

    private function explodeTerms(array|Builder|string $terms): array
    {
        if (is_string($terms)) {
            return array_filter(explode('|', $terms));
        }

        if (Compare::isQueryBuilder($terms)) {
            return $terms->get();
        }

        return $terms;
    }

    private function generator(): Generator
    {
        return Generator::fromCollection(handle: $this->params->get('collection', 'events'))
            ->when(
                value: $this->parseTerms(),
                callback: fn (Generator $generator, array $terms) => $generator->terms(terms: $terms)
            )
            ->when(
                value: $this->params->int('paginate'),
                callback: fn (Generator $generator, int $perPage) => $generator->pagination(perPage: $perPage)
            )->when(
                value: $this->params->bool('collapse_multi_days'),
                callback: fn (Generator $generator) => $generator->collapseMultiDays()
            );
    }

    private function getTermId(string $handle, Term|string $term): string
    {
        return $term instanceof Term ? $term->id() : Str::of($handle)->append('::', $term);
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

    private function queryFilters(Generator $generator): void
    {
    }

    private function parseTerms(): array
    {
        $taxonomyParams = collect($this->params)
            ->filter(fn ($value, $key) => Str::startsWith($key, 'taxonomy:'));

        if ($taxonomyParams->filter()->isEmpty()) {
            return [];
        }

        return $taxonomyParams
            ->flatMap(fn ($terms, $key) => $this->parseTermIds($key, $terms))
            ->all();
    }

    private function parseTermIds(string $key, array|Builder|string $terms): array
    {
        [$ignore, $handle] = explode(':', $key);

        return collect($this->explodeTerms($terms))
            ->map(fn (Term|string $term) => $this->getTermId(handle: $handle, term: $term))
            ->all();
    }
}
