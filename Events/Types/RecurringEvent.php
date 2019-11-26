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

    public function end(): Carbon
    {
        $end = $this->endDate();

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

        if ($after < $start) {
            return new Schedule(
                [
                    'date' => $start->toDateString(),
                    'start_time' => $this->startTime(),
                    'end_time' => $this->endTime(),
                ]
            );
        }

        $endDate = $this->endDate();

        if ($endDate && $after >= $endDate) {
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
        $day = Schedule::now();

        while (($day = $this->upcomingDate($day->start())) && $dates->count() <= $total) {
            $dates->push($day);
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
            $day = $this->upcomingDate($day->start());
        }

        return $days;
    }

    private function daily($after)
    {
        $start = $this->start();

        return $after->copy()
            ->day($after->day)
            ->hour($start->hour)
            ->minute($start->minute)
            ->second($start->second)
            ->addDay();
    }

    private function weekly($after)
    {
        $start = $this->start();

        return $after->copy()
            ->modify("next {$start->englishDayOfWeek}")
            ->hour($start->hour)
            ->minute($start->minute)
            ->second($start->second);
    }

    private function monthly($after)
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
}
