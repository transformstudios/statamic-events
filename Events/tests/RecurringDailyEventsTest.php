<?php

use Carbon\Carbon;
use Statamic\Testing\TestCase;
use Statamic\Addons\Events\Events;
use Statamic\Addons\Events\Types\EventFactory;

class RecurringDailyEventsTest extends TestCase
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
            'all_day' => true,
        ];

        $event = EventFactory::createFromArray($event);

        $this->assertNull($event->endDate());
        $this->assertNull($event->end());
    }

    public function test_get_null_next_date_if_after_end_date()
    {
        $event = [
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
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

    public function test_can_generate_next_day_if_during()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00');
        $event = [
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ];

        $nextDate = EventFactory::createFromArray($event)
            ->upcomingDate($startDate->copy()->addMinute(10));

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
            $events[] = $startDate->copy()->addDays($x);
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

    public function test_can_get_last_day_when_before()
    {
        Carbon::setTestNow(Carbon::now()->setTimeFromTimeString('10:30'));

        $this->events->add(EventFactory::createFromArray([
                       'id' => 'daily-event',
                       'start_date' => Carbon::now()->toDateString(),
                       'start_time' => '13:00',
                       'end_time' => '15:00',
                       'recurrence' => 'daily',
                       'end_date' => Carbon::now()->addDays(7)->toDateString(),
                   ]));

        $from = Carbon::now()->addDays(7);
        $to = Carbon::now()->endOfDay()->addDays(10);

        $events = $this->events->all($from, $to);

        $this->assertCount(1, $events);
    }

    public function test_generates_all_daily_occurrences_single_event_from_to()
    {
        Carbon::setTestNow(Carbon::now()->setTimeFromTimeString('10:30'));

        $this->events->add(EventFactory::createFromArray([
                       'id' => 'daily-event',
                       'start_date' => Carbon::now()->toDateString(),
                       'start_time' => '13:00',
                       'end_time' => '15:00',
                       'recurrence' => 'daily',
                       'end_date' => Carbon::now()->addDays(7)->toDateString(),
                   ]));

        $from = Carbon::now()->subDays(1);
        $to = Carbon::now()->endOfDay()->addDays(10);

        $events = $this->events->all($from, $to);

        $this->assertCount(8, $events);
    }

    public function test_generates_all_daily_occurrences_single_event_from_to_without_end_date()
    {
        Carbon::setTestNow(Carbon::now()->setTimeFromTimeString('10:30'));

        $this->events->add(EventFactory::createFromArray([
                       'id' => 'daily-event',
                       'start_date' => Carbon::now()->toDateString(),
                       'start_time' => '13:00',
                       'end_time' => '15:00',
                       'recurrence' => 'daily',
                   ]));

        $from = Carbon::now()->subDays(1);
        $to = Carbon::now()->endOfDay()->addDays(10);

        $events = $this->events->all($from, $to);

        $this->assertCount(11, $events);
    }
}
