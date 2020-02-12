<?php

namespace Statamic\Addons\Events\Types;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Statamic\Addons\Events\Schedule;

class SingleDayEvent extends Event
{
    /**
     * @param string|Carbon $after
     */
    public function upcomingDate($after = null): ?Schedule
    {
        if (is_null($after)) {
            $after = Carbon::now();
        }

        if (carbon($after) >= $this->end()) {
            return null;
        }

        return new Schedule([
            'date' => $this->start_date,
            'start_time' => $this->startTime(),
            'end_time' => $this->endTime(),
        ]);
    }

    public function upcomingDates($limit = 2, $offset = 0): Collection
    {
        if ($day = $this->upcomingDate(Carbon::now())) {
            return collect([$day]);
        }

        return collect([]);
    }

    public function datesBetween($from, $to): Collection
    {
        $start = $this->start();

        if ($start->between(carbon($from), carbon($to))) {
            return collect([Schedule::fromCarbon($start)]);
        }

        return collect([]);
    }
}
