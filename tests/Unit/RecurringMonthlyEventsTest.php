<?php

namespace TransformStudios\Events\Tests\Unit;

use Carbon\Carbon;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Tests\TestCase;

class RecurringMonthlyEventsTest extends TestCase
{
    /*
        public function test_can_generate_next_monthly_date_if_before()
        {
            $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

            $event = EventFactory::createFromArray(
                [
                    'start_date' => $startDate->toDateString(),
                    'start_time' => '11:00',
                    'end_time' => '12:00',
                    'recurrence' => 'monthly',
                ]
            );

            $nextDate = $event->upcomingDate($startDate->copy()->subDays(5));
            $this->assertEquals($startDate, $nextDate->start());

            $nextDate = $event->upcomingDate($startDate->copy()->subMonths(2)->subDay(5));
            $this->assertEquals($startDate, $nextDate->start());
        }

        public function test_can_generate_next_monthly_date_if_after()
        {
            Carbon::setTestNow(Carbon::parse('2019-11-24 10:50'));

            $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

            $event = EventFactory::createFromArray(
                [
                    'start_date' => $startDate->toDateString(),
                    'start_time' => '11:00',
                    'end_time' => '12:00',
                    'recurrence' => 'monthly',
                ]
            );

            $nextDate = $event->upcomingDate($startDate->copy()->addDays(5));
            $this->assertEquals(
                $startDate->copy()->addMonths(1),
                $nextDate->start()
            );

            $nextDate = $event->upcomingDate($startDate->copy()->addMonths(1)->subDays(5));
            $this->assertEquals(
                $startDate->copy()->addMonths(1),
                $nextDate->start()
            );

            $after = $startDate->copy()->addYears(1)->addMonths(1)->addDays(5);

            $nextDate = $event->upcomingDate($after);
            $this->assertEquals(
                $startDate->copy()->addYears(1)->addMonths(2),
                $nextDate->start()
            );
        }
    */
}
