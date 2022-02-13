<?php

namespace TransformStudios\Events\Tests;

use Carbon\Carbon;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events;
use TransformStudios\Events\Types\RecurringEvent;

class RecurringEveryXEventsTest extends TestCase
{
    /** @var Events */
    private $events;

    public function setUp(): void
    {
        parent::setUp();

        $this->events = new Events();
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    public function test_can_create_every_x_event()
    {
        $event = [
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'weeks',
        ];

        $event = EventFactory::createFromArray($event);

        $this->assertInstanceOf(RecurringEvent::class, $event);
    }

    public function test_get_end_date_null_if_no_end_date()
    {
        $event = [
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'recurrence' => 'every',
        ];

        $event = EventFactory::createFromArray($event);

        $this->assertNull($event->endDate());
    }

    public function test_get_null_next_date_if_after_end_date()
    {
        $event = [
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'end_date' => Carbon::now()->addDays(2)->toDateString(),
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'days',
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
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'days',
        ];

        $nextDate = EventFactory::createFromArray($event)
            ->upcomingDate($startDate->copy()->subDays(1));

        $this->assertEquals($startDate, $nextDate->start());

        $nextDate = EventFactory::createFromArray($event)
            ->upcomingDate(Carbon::now()->setTimeFromTimeString('10:59:00'));

        $this->assertEquals($startDate, $nextDate->start());
    }

    public function test_can_generate_current_date_if_during()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00');
        $event = [
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'days',
        ];

        $nextDate = EventFactory::createFromArray($event)
            ->upcomingDate($startDate->copy()->addMinute(10));

        $this->assertEquals($startDate, $nextDate->start());
    }

    public function test_can_generate_next_date_if_after_days()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'days',
        ];

        Carbon::setTestNow($startDate->copy()->addMinutes(1));

        $event = EventFactory::createFromArray($event);

        $nextDate = $event->upcomingDate(Carbon::now()->addHour());

        $this->assertEquals($startDate->addDays(2), $nextDate->start());

        $nextDate = $event->upcomingDate(Carbon::now()->addDays(2));

        $this->assertEquals($startDate, $nextDate->start());
    }

    public function test_can_generate_next_date_if_after_weeks()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'weeks',
        ];

        Carbon::setTestNow($startDate->copy()->addMinutes(1));

        $event = EventFactory::createFromArray($event);

        $nextDate = $event->upcomingDate(Carbon::now()->addHour());

        $this->assertEquals($startDate->addWeeks(2), $nextDate->start());

        $nextDate = $event->upcomingDate(Carbon::now()->addWeeks(2));

        $this->assertEquals($startDate, $nextDate->start());

        $nextDate = $event->upcomingDate(Carbon::now()->addDays(8));

        $this->assertEquals($startDate, $nextDate->start());
    }

    public function test_can_generate_next_dates_if_after_weeks()
    {
        $startDate = Carbon::parse('2021-03-04')->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => '2021-01-18',
            // 'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'weeks',
        ];

        $event = EventFactory::createFromArray($event);

        $date = $event->upcomingDate($startDate);

        $this->assertNotNull($date);

        $this->assertEquals('2021-03-15', $date->startDate());
    }

    public function test_can_generate_next_date_if_after_months()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'months',
        ];

        Carbon::setTestNow($startDate->copy()->addMinutes(1));

        $event = EventFactory::createFromArray($event);

        $nextDate = $event->upcomingDate(Carbon::now()->addHour());

        $this->assertEquals($startDate->addMonths(2), $nextDate->start());

        $nextDate = $event->upcomingDate(Carbon::now()->addMonths(2));

        $this->assertEquals($startDate, $nextDate->start());
    }

    public function test_can_generate_next_x_dates_from_today_before_event_time()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');
        $event = EventFactory::createFromArray(
            [
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'days',
            ]
        );

        for ($x = 0; $x < 2; $x++) {
            $events[] = $startDate->copy()->addDays($x * 2);
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
        $event = EventFactory::createFromArray(
            [
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'days',
            ]
        );

        for ($x = 0; $x < 3; $x++) {
            $events[] = $startDate->copy()->addDays($x * 2);
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
                'end_date' => $startDate->copy()->addDays(5)->toDateString(),
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'days',
            ]
        );

        for ($x = 1; $x <= 2; $x++) {
            $events[] = $startDate->copy()->addDays($x * 2 + 1);
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

        $event = [
            'id' => 'daily-event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'days',
            'end_date' => Carbon::now()->addDays(8)->toDateString(),
        ];

        $this->events->add(EventFactory::createFromArray($event));

        $from = Carbon::now()->addDays(7);
        $to = Carbon::now()->endOfDay()->addDays(10);

        $events = $this->events->all($from, $to);

        $this->assertCount(1, $events);

        $event['start_date'] = Carbon::now()->addDays(8)->toDateString();

        $this->assertEquals($event, $events[0]->toArray());
    }

    public function test_generates_all_daily_occurrences_single_event_from_to_with_end_date()
    {
        Carbon::setTestNow(Carbon::now()->setTimeFromTimeString('10:30'));

        $this->events->add(EventFactory::createFromArray(
            [
                'id' => 'daily-event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '13:00',
                'end_time' => '15:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'days',
                'end_date' => Carbon::now()->addDays(8)->toDateString(),
            ]
        ));

        $from = Carbon::now()->subDays(1);
        $to = Carbon::now()->endOfDay()->addDays(10);

        $events = $this->events->all($from, $to);

        $this->assertCount(5, $events);
    }

    public function test_generates_all_daily_occurrences_single_event_from_to_without_end_date()
    {
        Carbon::setTestNow(Carbon::now()->setTimeFromTimeString('10:30'));

        $this->events->add(EventFactory::createFromArray(
            [
                'id' => 'daily-event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '13:00',
                'end_time' => '15:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'days',
            ]
        ));

        $from = Carbon::now()->subDays(1);
        $to = Carbon::now()->endOfDay()->addDays(10);

        $events = $this->events->all($from, $to);

        $this->assertCount(6, $events);
    }

    public function test_can_generate_next_x_weeks_if_in_different_weeks()
    {
        $event = EventFactory::createFromArray(
            [
                'start_date' => '2020-01-03',
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'weeks',
            ]
        );

        $day = $event->upcomingDate(Carbon::parse('2021-01-31'));

        $this->assertNotNull($day);
        $this->assertEquals('2021-02-12', $day->startDate());
    }

    public function test_returns_null_when_dates_between_dont_have_event()
    {
        $event = EventFactory::createFromArray(
            [
                'start_date' => '2021-01-29',
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'weeks',
            ]
        );

        $dates = $event->datesBetween('2021-02-18', '2021-02-19');

        $this->assertEmpty($dates);
    }
}
