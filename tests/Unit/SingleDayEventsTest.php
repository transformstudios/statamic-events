<?php

namespace TransformStudios\Events\Tests;

use Carbon\Carbon;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Types\SingleDayEvent;

class SingleDayEventsTest extends TestCase
{
    /** @var SingleDayEvent */
    private $allDayEvent;

    /** @var SingleDayEvent */
    private $nonAllDayEvent;

    /** @var SingleDayEvent */
    private $recurrenceNoneEvent;

    public function setUp(): void
    {
        parent::setUp();

        $this->allDayEvent = EventFactory::createFromArray([
            'all_day' => true,
            'start_date' => '2019-11-27',
        ]);

        $this->nonAllDayEvent = EventFactory::createFromArray([
            'start_date' => '2019-11-27',
            'start_time' => '11:00',
            'end_time' => '12:00',
        ]);

        $this->recurrenceNoneEvent = EventFactory::createFromArray([
            'start_date' => '2019-11-27',
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 0,
        ]);
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

    public function test_can_create_single_day_event()
    {
        $event = EventFactory::createFromArray(
            [
                'start_date' => '2019-11-27',
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'false',
            ]
        );

        $this->assertTrue($this->allDayEvent instanceof SingleDayEvent);
        $this->assertTrue($this->nonAllDayEvent instanceof SingleDayEvent);
        $this->assertTrue($this->recurrenceNoneEvent instanceof SingleDayEvent);
        $this->assertTrue($event instanceof SingleDayEvent);
        $this->assertFalse($event->isRecurring());
        $this->assertFalse($event->isMultiDay());
    }

    public function test_can_get_start()
    {
        $this->assertEquals(Carbon::parse('2019-11-27 00:00'), $this->allDayEvent->start());
        $this->assertEquals(Carbon::parse('2019-11-27 11:00'), $this->nonAllDayEvent->start());
    }

    public function test_can_get_end()
    {
        $endOfDayTime = Carbon::now()->endOfDay()->format('G:i');

        $endOfDay = Carbon::parse('2019-11-27')->setTimeFromTimeString($endOfDayTime);
        $this->assertEquals($endOfDay, $this->allDayEvent->end());
        $this->assertEquals(Carbon::parse('2019-11-27 12:00'), $this->nonAllDayEvent->end());
    }

    public function test_can_get_end_when_none_set()
    {
        $event = EventFactory::createFromArray([
            'start_date' => '2019-11-27',
            'start_time' => '11:00',
        ]);

        $endOfDayTime = Carbon::now()->endOfDay()->format('G:i');

        $endOfDay = Carbon::parse('2019-11-27')->setTimeFromTimeString($endOfDayTime);
        $this->assertEquals($endOfDay, $event->end());
    }

    public function test_can_get_start_when_none_set()
    {
        $event = EventFactory::createFromArray([
            'start_date' => '2019-11-27',
        ]);

        $startOfDayTime = Carbon::now()->startOfDay()->toTimeString();

        $startOfDay = Carbon::parse('2019-11-27')->setTimeFromTimeString($startOfDayTime);
        $this->assertEquals($startOfDay, $event->start());
    }

    public function test_get_null_next_date_if_after_end_date()
    {
        $this->assertNull(
            $this->allDayEvent->upcomingDate(Carbon::parse('2019-11-28'))
        );
        $this->assertNull(
            $this->nonAllDayEvent->upcomingDate(Carbon::parse('2019-11-28'))
        );
    }

    public function test_can_generate_next_datetime_if_before()
    {
        $this->assertEquals(
            Carbon::parse('2019-11-27')->setTimeFromTimeString('00:00:00'),
            $this->allDayEvent->upcomingDate(Carbon::parse('2019-11-22'))->start()
        );
        $this->assertEquals(
            Carbon::parse('2019-11-27')->setTimeFromTimeString('11:00:00'),
            $this->nonAllDayEvent->upcomingDate(Carbon::parse('2019-11-22'))->start()
        );
        $this->assertEquals(
            Carbon::parse('2019-11-27')->setTimeFromTimeString('12:00:00'),
            $this->nonAllDayEvent->upcomingDate(Carbon::parse('2019-11-22'))->end()
        );
    }

    public function test_returns_start_if_during()
    {
        $this->assertEquals(
            Carbon::parse('2019-11-27')->setTimeFromTimeString('11:00:00'),
            $this->nonAllDayEvent->upcomingDate(Carbon::parse('2019-11-27 11:30'))->start()
        );
        $this->assertEquals(
            Carbon::parse('2019-11-27')->setTimeFromTimeString('00:00:00'),
            $this->allDayEvent->upcomingDate(Carbon::parse('2019-11-27 11:30'))->start()
        );
    }

    public function test_can_generate_next_dates_when_before_start()
    {
        Carbon::setTestNow(Carbon::parse('2019-11-23'));
        $dates = collect([
            Carbon::parse('2019-11-27 00:00'),
        ]);

        $nextDates = $this->allDayEvent->upcomingDates();

        $this->assertCount(1, $nextDates);

        $this->assertEquals($dates[0], $nextDates[0]->start());

        $dates = collect([
            Carbon::parse('2019-11-27 11:00'),
        ]);

        $nextDates = $this->nonAllDayEvent->upcomingDates();

        $this->assertCount(1, $nextDates);

        $this->assertEquals($dates[0], $nextDates[0]->start());
    }

    public function test_can_generate_between_dates()
    {
        $dates = collect([
            Carbon::parse('2019-11-27 00:00'),
        ]);

        $nextDates = $this->allDayEvent->datesBetween(Carbon::parse('2019-11-26'), Carbon::parse('2019-11-28'));

        $this->assertCount(1, $nextDates);

        $this->assertEquals($dates[0], $nextDates[0]->start());

        $dates = collect([
            Carbon::parse('2019-11-27 11:00'),
            Carbon::parse('2019-11-27 12:00'),
        ]);

        $nextDates = $this->nonAllDayEvent->datesBetween(Carbon::parse('2019-11-26'), Carbon::parse('2019-11-28'));

        $this->assertCount(1, $nextDates);

        $this->assertEquals($dates[0], $nextDates[0]->start());
        $this->assertEquals($dates[1], $nextDates[0]->end());

        $this->assertEmpty(
            $this->allDayEvent->datesBetween(Carbon::parse('2019-11-28'), Carbon::parse('2019-11-29'))
        );
        $this->assertEmpty(
            $this->nonAllDayEvent->datesBetween(Carbon::parse('2019-11-28'), Carbon::parse('2019-11-29'))
        );
    }
}
