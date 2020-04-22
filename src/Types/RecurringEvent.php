<?php

namespace TransformStudios\Events\Types;

use Carbon\Carbon;
use Statamic\API\Str;
use Statamic\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Addons\Events\Schedule;

class RecurringEvent extends Event
{
    public function __construct($data)
    {
        $periodMap = [
            'daily' => 'days',
            'weekly' => 'weeks',
            'monthly' => 'months',
        ];

        // if type is daily/weekly/monthly, set the period and interval appropriately
        if (array_key_exists($data['recurrence'], $periodMap)) {
            $data['period'] = $periodMap[$data['recurrence']];
            $data['interval'] = 1;
        }

        parent::__construct($data);
    }

    public function isRecurring(): bool
    {
        return true;
    }

    public function endDate(): ?Carbon
    {
        if ($date = Arr::get($this->data, 'end_date')) {
            return Carbon::parse($date);
        }

        return null;
    }

    public function end(): ?Carbon
    {
        if (! $end = $this->endDate()) {
            return null;
        }

        if ($this->isAllDay()) {
            return $end->endOfDay();
        }

        return $end->setTimeFromTimeString($this->endTime());
    }

    /**
     * @param null|Carbon $after
     */
    public function upcomingDate($after = null): ?Schedule
    {
        if (is_null($after)) {
            $after = Carbon::now();
        }

        $start = $this->start();

        if ($after < $start->copy()->setTimeFromTimeString($this->endTime())) {
            return new Schedule(
                [
                    'date' => $start->toDateString(),
                    'start_time' => $this->startTime(),
                    'end_time' => $this->endTime(),
                ]
            );
        }

        $end = $this->end();

        if ($end && $after >= $end) {
            return null;
        }

        $nextDate = $this->next($after);

        if ($this->excludedDate($nextDate)) {
            // add the period*interval to get to the next one
            return $this->upcomingDate($this->addInterval($nextDate));
        }

        return new Schedule(
            [
                'date' => $nextDate->toDateString(),
                'start_time' => $this->startTime(),
                'end_time' => $this->endTime(),
            ]
        );
    }

    public function upcomingDates($limit = 2, $offset = 0): Collection
    {
        $total = $offset ? $limit * $offset : $limit;

        $dates = collect();
        $after = Carbon::now();

        while (($day = $this->upcomingDate($after)) && $dates->count() <= $total) {
            $dates->push($day);

            $after = $day->start()->{$this->periodMethod('add')}($this->interval);
        }

        return $dates->slice($offset, $limit);
    }

    public function datesBetween($from, $to): Collection
    {
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);

        if (($from->startOfDay() > $to->endOfDay()) ||
            ($this->start()->isAfter($to)) ||
            ($this->end() && $this->end()->isBefore($from))
        ) {
            return collect();
        }

        $days = collect();

        $day = $this->upcomingDate($from);

        while ($day && $day->start() < $to) {
            $days->push($day);
            $day = $this->upcomingDate($day->start()->{$this->periodMethod('add')}($this->interval));
        }

        return $days;
    }

    private function excludedDate(Carbon $date)
    {
        return collect($this->get('except'))
            ->map(function ($item, $key) {
                return $item['date'];
            })
            ->contains($date->toDateString());
    }

    // this is guaranteed to be AFTER the start
    private function next(Carbon $after): Carbon
    {
        $start = $this->start()->startOfDay();
        $diff = $after->{$this->periodMethod('diffIn')}($start);

        $periods = intdiv($diff, $this->interval);

        if ($diff % $this->interval) {
            $periods++;
        }

        // if the interval is one the above `mod` doesn't work right and we need
        // to check a few things
        // @todo dis be some ugly code, refactor
        if ($this->interval == 1) {
            // we're in a subsuquent week but after (day-wise) the start, so go to
            // the next period
            if ($this->period == 'weeks' && $after->dayOfWeek > $start->dayOfWeek) {
                $periods++;
            }

            // we're in a subsequent month but we're after (date-wise) the start so
            // go to the next period
            if ($this->period == 'months' && $after->year == $start->year && $after->day > $start->day) {
                $periods++;
            }
        }

        $increment = ($periods ?: 1) * $this->interval;

        return $start->{$this->periodMethod('add')}($increment);
    }

    private function addInterval(Carbon $date)
    {
        return $date->{$this->periodMethod('add')}($this->get('interval'));
    }

    private function periodMethod(string $prefix): string
    {
        return $prefix.Str::toTitleCase($this->period);
    }
}
