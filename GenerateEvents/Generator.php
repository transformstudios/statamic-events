<?php

namespace Statamic\Addons\GenerateEvents;

use Carbon\Carbon;

class Generator
{
    private $startDate;
    private $endDate;
    private $recurrenceType;
    //private $recurrenceInterval = 1;

    /**
     * Undocumented function
     *
     * @param mixed $startDate
     * @param string $type
     * @param string $interval
     * @param mixed $endDate
     */
    public function __construct($startDate, $type, $endDate = null)
    {
        $this->startDate = clone carbon($startDate);
        $this->endDate = $endDate ? clone carbon($endDate) : null;
        $this->recurrenceType = $type;
        //$this->recurrenceInterval = $interval;
    }

    /**
     * Get the next occurrence AFTER the given date function
     *
     * @param string|Carbon $afterDate
     * @return Carbon
     */
    public function nextOccurrence($afterDate = null)
    {
        $afterDate = clone carbon($afterDate ?? time());

        if ($afterDate < $this->startDate) {
            return clone $this->startDate;
        }

        if ($this->endDate && $afterDate >= $this->endDate) {
            return null;
        }

        $nextOccurrence = clone $this->startDate;

        switch ($this->recurrenceType) {
            case 'daily':
                $nextOccurrence = (clone $afterDate)->addDay();
                break;
            case 'weekly':
                $englishDayOfWeek = $this->startDate->englishDayOfWeek;

                $nextOccurrence = $afterDate->modify("next {$englishDayOfWeek}");
                $nextOccurrence->hour($this->startDate->hour);
                $nextOccurrence->minute($this->startDate->minute);
                break;
            case 'monthly':
                $nextOccurrence = (clone $this->startDate)
                    ->month($afterDate->month)
                    ->year($afterDate->year);
                if ($afterDate->day >= $this->startDate->day) {
                    $nextOccurrence->addMonth();
                }
                break;
            case 'every_x_weeks':
                throw \Exception('not implemented');
    }

        return clone $nextOccurrence;
    }

    public function nextOccurrences($occurrences, $after)
    {
        if ($this->endDate && ($after > $this->endDate)) {
            return [];
        }

        $currentDate = $this->nextOccurrence($after);
        $dates = [];
        $x = 0;

        while ($currentDate && ($x < $occurrences)) {
            $dates[$x++] = $currentDate;
            $currentDate = $this->nextOccurrence($currentDate);
        }

        return $dates;
    }
}