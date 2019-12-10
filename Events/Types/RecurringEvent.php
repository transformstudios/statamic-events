<?php

namespace Statamic\Addons\Events\Types;

use Carbon\Carbon;
use Statamic\API\Arr;
use Illuminate\Support\Collection;
use Statamic\Addons\Events\Schedule;

class RecurringEvent extends Event
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
                'date' => $this->{$this->recurrence}($after)->toDateString(),
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
            $after = $day->start()->modify("next {$this->recurrenceUnit()}");
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
            $day = $this->upcomingDate($day->start()->modify("next {$this->recurrenceUnit()}"));
        }

        return $days;
    }

    private function daily(Carbon $after)
    {
        $start = $this->start();

        $next = $after->copy()
            ->day($after->day)
            ->hour($start->hour)
            ->minute($start->minute)
            ->second($start->second);

        if ($after >= $next->copy()->setTimeFromTimeString($this->endTime())) {
            $next->addDay();
        }

        return $next;
    }

    private function weekly(Carbon $after)
    {
        $start = $this->start();

        $next = $after->copy()
            ->hour($start->hour)
            ->minute($start->minute)
            ->second($start->second);

        // during is if on same day and time is >=start && < end
        $during = $after->isBetween(
            $after->copy()->setTimeFromTimeString($this->startTime()),
            $after->copy()->setTimeFromTimeString($this->endTime()),
        );

        // if $after is on the same day of the week as $start
        // AND it is DURING the time, DO NOT go to the next week
        if (!($after->dayOfWeek == $start->dayOfWeek && $during)) {
            $next->modify("next {$start->englishDayOfWeek}");
        }

        return $next;
    }

    private function monthly(Carbon $after)
    {
        $start = $this->start();

        $date = $start
            ->month($after->month)
            ->year($after->year);

        if ($after->day >= $start->day) {
            $date->addMonth();
        }

        return $date;
    }

    private function recurrenceUnit()
    {
        switch ($this->recurrence) {
            case 'daily':
                return 'day';
            case 'weekly':
                return 'week';
            case 'monthly':
                return 'month';
            case 'yearly':
                return 'year';
        }
    }
}
