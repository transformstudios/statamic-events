<?php

namespace TransformStudios\Events\Tests;

use Carbon\Carbon;
use Statamic\Testing\TestCase;
use Statamic\Addons\Events\Events;
use Statamic\Addons\Events\EventFactory;

class RecurringMonthlyEventsTest extends TestCase
{
    /** @var Events */
    private $events;

    public function setUp()
    {
        parent::setUp();

        $this->events = new Events();
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

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
        Carbon::setTestNow(carbon('2019-11-24 10:50'));

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

        $nextDate = $event->upcomingDate($startDate->copy()->addYears(1)->addMonths(1)->addDays(5));
        $this->assertEquals(
            $startDate->copy()->addYears(1)->addMonths(1),
            $nextDate->start()
        );
    }
}
