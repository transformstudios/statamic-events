<?php

namespace TransformStudios\Events\Types;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Collection;
use RRule\RRuleInterface;
use Statamic\Entries\Entry;

abstract class Event
{
    abstract protected function rule(): RRuleInterface;

    public function __construct(protected Entry $event)
    {
    }

    public function occurrencesBetween(string|Carbon $from, string|Carbon $to): Collection
    {
        $dates = $this->rule()->getOccurrencesBetween($from, $to);

        return $this->collect($dates);
    }

    public function nextOccurrences(int $limit = 1): Collection
    {
        $dates = $this->rule()->getOccurrencesAfter(now(), true, $limit);

        return $this->collect($dates);
    }

    private function collect(array $dates): Collection
    {
        return collect($dates)
            ->map(fn (DateTimeInterface $date) => $this->supplement(Carbon::parse($date)));
    }

    private function supplement(CarbonInterface $date): Entry
    {
        $occurence = clone $this->event;
        $occurence->setSupplement('start', $date->setTimeFromTimeString($this->endTime()));

        if ($endTime = $this->end_time) {
            $occurence->setSupplement('end', $date->setTimeFromTimeString($endTime));
        }

        return $occurence;
    }

    public function __get(string $key): mixed
    {
        return $this->event->get($key);
    }

    public function isAllDay(): bool
    {
        return $this->event->get('all_day', false);
    }

    public function isMultiDay(): bool
    {
        return $this->event->get('multi_day', false);
    }

    public function startTime(): string
    {
        return $this->start_time ?? now()->startOfDay()->format('G:i');
    }

    public function endTime(): string
    {
        return $this->end_time ?? now()->endOfDay()->format('G:i');
    }

    public function start(): Carbon
    {
        return Carbon::parse($this->start_date)
            ->setTimeFromTimeString($this->startTime());
    }

    public function end(): ?Carbon
    {
        if (! $endDate = $this->end_date) {
            return null;
        }

        return Carbon::parse($endDate)->setTimeFromTimeString($this->endTime());
    }
}
