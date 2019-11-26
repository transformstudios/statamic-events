<?php

use Carbon\Carbon;
use Statamic\Testing\TestCase;
use Statamic\Addons\Events\Events;
use Statamic\Addons\Events\Types\EventFactory;

class RecurringEventsTest extends TestCase
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

    public function test_get_end_date_null_if_no_end_date()
    {
        $event = [
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'recurrence' => 'daily',
        ];

        $event = EventFactory::createFromArray($event);

        $this->assertNull($event->endDate());
    }

    public function test_get_null_next_date_if_after_end_date()
    {
        $event = [
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_date' => Carbon::now()->addDays(2)->toDateString(),
            'recurrence' => 'daily',
        ];

        $event = EventFactory::createFromArray($event);

        $nextDate = $event->upcomingDate(Carbon::now()->addDay(3));

        $this->assertNull($nextDate);
    }

    public function test_can_generate_next_day_if_before()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00');
        $event = [
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ];

        $nextDate = EventFactory::createFromArray($event)
            ->upcomingDate($startDate->copy()->subDays(1));

        $this->assertEquals($startDate, $nextDate->start());

        $nextDate = EventFactory::createFromArray($event)
            ->upcomingDate(Carbon::now()->setTimeFromTimeString('10:59:00'));

        $this->assertEquals($startDate, $nextDate->start());
    }

    public function test_can_generate_next_day_if_after()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = [
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ];

        Carbon::setTestNow($startDate->copy()->addMinutes(1));

        $event = EventFactory::createFromArray($event);

        $nextDate = $event->upcomingDate(Carbon::now()->addHour());

        $this->assertEquals($startDate->addDay(), $nextDate->start());
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

        $nextDate = $event->upcomingDate(Carbon::now()->addWeek());

        $this->assertEquals($startDate->addWeeks(2), $nextDate->start());
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
            $startDate->copy()->addYears(1)->addMonths(2),
            $nextDate->start()
        );
    }

    public function test_can_generate_next_x_dates_from_today_before_event_time()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');
        $event = EventFactory::createFromArray(
            [
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ]
        );

        for ($x = 0;$x < 2;$x++) {
            $events[] = $startDate->copy()->addDays($x);
        }

        $this->events->add($event);

        Carbon::setTestNow($startDate->copy()->subMinutes(1));

        $nextDates = $this->events->upcoming(2);

        $this->assertCount(2, $nextDates);

        $this->assertEquals($events[0], $nextDates[0]->start());
        $this->assertEquals($events[1], $nextDates[1]->start());
    }

    public function test_can_generate_next_x_dates_from_today()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');
        $event = EventFactory::createFromArray([
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
        ]);

        for ($x = 0;$x < 3;$x++) {
            $events[] = $startDate->copy()->addDays($x + 1);
        }

        $this->events->add($event);

        Carbon::setTestNow($startDate->copy()->addMinutes(1));

        $nextDates = $this->events->upcoming(3);

        $this->assertCount(3, $nextDates);

        $this->assertEquals($events[0], $nextDates[0]->start());
        $this->assertEquals($events[1], $nextDates[1]->start());
        $this->assertEquals($events[2], $nextDates[2]->start());
    }

    public function test_generates_all_occurrences_when_daily_after_start_date()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = EventFactory::createFromArray(
            [
                'start_date' => $startDate->copy()->addDay()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'end_date' => $startDate->copy()->addDays(3)->toDateString(),
                'recurrence' => 'daily',
            ]
        );

        for ($x = 2; $x <= 3; $x++) {
            $events[] = $startDate->copy()->addDays($x);
        }

        $this->events->add($event);

        Carbon::setTestNow($startDate->copy()->addDays(1)->addHour(1));
        $nextEvents = $this->events->upcoming(3);

        $this->assertCount(2, $nextEvents);

        $this->assertEquals($events[0], $nextEvents[0]->start());
        $this->assertEquals($events[1], $nextEvents[1]->start());
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

    public function test_generates_next_occurrence_when_multiple_events()
    {
        $this->events->add(EventFactory::createFromArray([
                   'start_date' => Carbon::now()->subDays(8)->toDateString(),
                   'start_time' => '11:00',
                   'recurrence' => 'weekly',
                   'end_date' => Carbon::now()->addWeeks(3)->toDateString(),
                   'end_time' => '12:00',
               ]));

        $this->events->add(EventFactory::createFromArray([
                   'start_date' => Carbon::now()->subDays(2)->toDateTimeString(),
                   'start_time' => '13:00',
                   'recurrence' => 'daily',
                   'end_date' => Carbon::now()->addDays(5)->toDateString(),
                   'end_time' => '15:00',
               ]));

        $nextEvent = $this->events->upcoming(1);

        Carbon::setTestNow(Carbon::now()->setTimeFromTimeString('14:00:00'));

        $this->assertEquals(
            Carbon::now()->addDays(1)->setTimeFromTimeString('13:00:00'),
            $nextEvent->start()
        );
    }

    public function test_generates_all_occurrences_single_event_from_to()
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

    public function test_generates_all_occurrences_multiple_events_from_to()
    {
        Carbon::setTestNow(Carbon::now()->setTimeFromTimeString('10:30'));

        $this->events->add(EventFactory::createFromArray([
                       'id' => 'weekly-event',
                       'start_date' => Carbon::now()->toDateString(),
                       'start_time' => '11:00',
                       'end_time' => '12:00',
                       'recurrence' => 'weekly',
                       'end_date' => Carbon::now()->addWeeks(11)->toDateString(),
                   ]));

        $this->events->add(EventFactory::createFromArray([
                       'id' => 'daily-event',
                       'start_date' => Carbon::now()->toDateString(),
                       'start_time' => '13:00',
                       'end_time' => '15:00',
                       'recurrence' => 'daily',
                       'end_date' => Carbon::now()->addDays(7)->toDateString(),
                   ]));

        $from = Carbon::now()->subDays(1);
        $to = Carbon::now()->addDays(10);

        $events = $this->events->all($from, $to);

        // weekly has 2
        // daily has 8?
        $this->assertCount(10, $events);

        $this->assertEquals(
            Carbon::now()->setTimeFromTimeString('11:00'),
            $events[0]->start()
        );
        $this->assertEquals(
            Carbon::now()->setTimeFromTimeString('13:00'),
            $events[1]->start()
        );
    }
}
