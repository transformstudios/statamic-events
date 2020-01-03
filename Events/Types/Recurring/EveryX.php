<?php

namespace Statamic\Addons\Events\Types\Recurring;

use Carbon\Carbon;
use Statamic\API\Arr;
use Statamic\API\Str;
use Illuminate\Support\Collection;
use Statamic\Addons\Events\Schedule;
use Statamic\Addons\Events\Types\RecurringEvent;

class EveryX extends RecurringEvent
{
    public function endDate(): ?Carbon
    {
        if ($date = Arr::get($this->data, 'end_date')) {
            return carbon($date);
        }

        return null;
    }

    public function end(): ?Carbon
    {
        $end = $this->endDate();

        if ($this->isAllDay()) {
            return $end->endOfDay();
        }

        return $end ? $end->setTimeFromTimeString($this->endTime()) : null;
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

        return new Schedule(
            [
                'date' => $this->next($after)->toDateString(),
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

            // think I want `$after->modify('next day/week/month');
            $after = $day->start()->{$this->periodMethod('add')}($this->interval);
        }

        return $dates->slice($offset, $limit);
    }

    public function datesBetween($from, $to): Collection
    {
        $from = carbon($from);
        $to = carbon($to);

        if (($from->startOfDay() > $to->endOfDay()) ||
            ($this->start()->isAfter($to)) ||
            ($this->end()->isBefore($from))
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

    // this is guaranteed to be AFTER the start
    private function next(Carbon $after)
    {
        $start = $this->start()->startOfDay();
        $diff = $after->{$this->periodMethod('diffIn')}($start);

        $periods = intdiv($diff, $this->interval);

        if ($diff % $this->interval) {
            $periods++;
        }

        return $start->{$this->periodMethod('add')}(($periods ?: 1) * $this->interval);
    }

    private function periodMethod(string $prefix): string
    {
        return $prefix . Str::toTitleCase($this->period);
    }
}
