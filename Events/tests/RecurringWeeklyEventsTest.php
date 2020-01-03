<?php

use Carbon\Carbon;
use Statamic\Testing\TestCase;
use Statamic\Addons\Events\Events;
use Statamic\Addons\Events\Types\EventFactory;

class RecurringWeeklyEventsTest extends TestCase
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

    public function test_can_generate_next_weekly_date_when_before()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = EventFactory::createFromArray(
            [
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'weekly',
            ]
        );

        $nextDate = $event->upcomingDate(Carbon::now()->subDays(3));
        $this->assertEquals($startDate, $nextDate->start());

        $nextDate = $event->upcomingDate(Carbon::now()->subWeeks(1)->subDays(3));
        $this->assertEquals($startDate, $nextDate->start());
    }

    public function test_can_generate_next_weekly_date_if_during()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = EventFactory::createFromArray(
            [
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'weekly',
            ]
        );

        $nextDate = $event
            ->upcomingDate($startDate->copy()->addMinute(10));

        $this->assertEquals($startDate, $nextDate->start());

        $nextDate = $event
            ->upcomingDate($startDate->copy()->addWeek()->addMinute(10));

        $this->assertEquals($startDate->addWeek(), $nextDate->start());
    }

    public function test_can_generate_next_weekly_date_when_after()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = EventFactory::createFromArray(
            [
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'weekly',
            ]
        );

        $nextDate = $event->upcomingDate(Carbon::now()->addDays(3));

        $this->assertEquals($startDate->addWeek(), $nextDate->start());
    }

    public function test_can_generate_next_weekly_date_when_a_week_after()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = EventFactory::createFromArray(
            [
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'weekly',
            ]
        );

        $nextDate = $event->upcomingDate($startDate->copy()->addWeek());

        $this->assertEquals($startDate->addWeeks(1), $nextDate->start());
    }

    public function test_generates_all_occurrences_when_weekly_after_start_date()
    {
        $startDate = Carbon::now()->subDay()->setTimeFromTimeString('11:00:00');

        $event = EventFactory::createFromArray(
            [
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'end_date' => $startDate->copy()->addWeeks(3)->toDateString(),
                'recurrence' => 'weekly',
            ]
        );

        for ($x = 1;$x <= 3;$x++) {
            $events[] = $startDate->copy()->addWeeks($x);
        }

        $this->events->add($event);

        $nextEvents = $this->events->upcoming(4);

        $this->assertCount(3, $nextEvents);

        $this->assertEquals($events[0], $nextEvents[0]->start());
        $this->assertEquals($events[1], $nextEvents[1]->start());
        $this->assertEquals($events[2], $nextEvents[2]->start());
    }

    public function test_generates_all_weekly_occurrences_single_event_from_to()
    {
        $startDate = Carbon::now();

        $this->events->add(EventFactory::createFromArray([
                   'id' => 'weekly-event',
                   'start_date' => $startDate->copy()->subDays(8)->toDateString(),
                   'start_time' => '11:00',
                   'recurrence' => 'weekly',
                   'end_date' => $startDate->copy()->addWeeks(3)->toDateString(),
                   'end_time' => '12:00',
               ]));

        $from = Carbon::now()->subDays(3);
        $to = $from->copy()->addDays(15);

        $events = $this->events->all($from, $to);

        $this->assertCount(2, $events);

        $this->assertEquals(
            Carbon::now()->subDays(1)->setTimeFromTimeString('11:00:00'),
            $events[0]->start()
        );
        $this->assertEquals(
            Carbon::now()->addDays(6)->setTimeFromTimeString('11:00:00'),
            $events[1]->start()
        );
    }
}
