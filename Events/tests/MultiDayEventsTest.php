<?php

use Carbon\Carbon;
use Statamic\Testing\TestCase;
use Statamic\Addons\Events\Events;
use Statamic\Addons\Events\EventFactory;
use Statamic\Addons\Events\Types\MultiDayEvent;

class MultiDayEventsTest extends TestCase
{
    /** @var MultiDayEvent */
    private $event;

    /** @var MultiDayEvent */
    private $allDayEvent;

    public function setUp()
    {
        parent::setUp();

        $this->event = EventFactory::createFromArray(
            [
                'multi_day' => true,
                'days' => [
                    [
                        'date' => '2019-11-23',
                        'start_time' => '19:00',
                        'end_time' => '21:00',
                    ],
                    [
                        'date' => '2019-11-24',
                        'start_time' => '11:00',
                        'end_time' => '15:00',
                    ],
                    [
                        'date' => '2019-11-25',
                        'start_time' => '11:00',
                        'end_time' => '15:00',
                    ],
                ],
            ]
        );

        $this->brokenEvent = EventFactory::createFromArray(
            [
                'multi_day' => true,
                'days' => [
                    [
                        'date' => '2019-11-23',
                        'start_time' => '19:00',
                    ],
                    [
                        'date' => '2019-11-24',
                        'end_time' => '15:00',
                    ],
                ],
            ]
        );

        $this->allDayEvent = EventFactory::createFromArray(
            [
                'multi_day' => true,
                'all_day' => true,
                'days' => [
                    [
                        'date' => '2019-11-20',
                    ],
                    [
                        'date' => '2019-11-21',
                    ],
                ],
            ]
        );
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

    public function test_can_create_multi_day_event()
    {
        $this->assertTrue($this->event instanceof MultiDayEvent);
        $this->assertTrue($this->allDayEvent instanceof MultiDayEvent);
        $this->assertTrue($this->brokenEvent instanceof MultiDayEvent);
        $this->assertTrue($this->event->isMultiDay());
        $this->assertTrue($this->allDayEvent->isMultiDay());
        $this->assertTrue($this->brokenEvent->isMultiDay());
    }

    public function test_can_get_start()
    {
        $this->assertEquals(carbon('2019-11-23 19:00'), $this->event->start());
        $this->assertEquals(carbon('2019-11-20 0:00'), $this->allDayEvent->start());
    }

    public function test_can_get_end()
    {
        $this->assertEquals(carbon('2019-11-25 15:00'), $this->event->end());
        $this->assertEquals(carbon('2019-11-21 23:59'), $this->allDayEvent->end());
    }

    public function test_get_null_next_date_if_after_end_date()
    {
        $this->assertNull(
            $this->event->upcomingDate(carbon('2019-11-26'))
        );
    }

    public function test_can_generate_next_datetime_if_before()
    {
        $this->assertEquals(
            carbon('2019-11-23')->setTimeFromTimeString('19:00:00'),
            $this->event->upcomingDate(carbon('2019-11-22'))->start()
        );
    }

    public function test_can_generate_next_datetime_if_during()
    {
        $this->assertEquals(
            carbon('2019-11-24')->setTimeFromTimeString('11:00:00'),
            $this->event->upcomingDate(carbon('2019-11-24 10:00'))->start()
        );
    }

    public function test_can_generate_next_dates_when_before_start()
    {
        $dates = collect([
            carbon('2019-11-23 19:00'),
            carbon('2019-11-24 11:00'),
        ]);

        Carbon::setTestNow(carbon('2019-11-23'));

        $nextDates = $this->event->upcomingDates();

        $this->assertCount(2, $nextDates);

        $this->assertEquals($dates[0], $nextDates[0]->start());
        $this->assertEquals($dates[1], $nextDates[1]->start());
    }

    public function test_can_generate_next_dates_when_during()
    {
        $dates = collect([
            carbon('2019-11-24 11:00'),
            carbon('2019-11-25 11:00'),
        ]);

        Carbon::setTestNow(carbon('2019-11-24'));

        $nextDates = $this->event->upcomingDates();

        $this->assertCount(2, $nextDates);

        $this->assertEquals($dates[0], $nextDates[0]->start());
        $this->assertEquals($dates[1], $nextDates[1]->start());

        $nextDates = $this->event->upcomingDates(3);

        $this->assertEquals($dates[0], $nextDates[0]->start());
        $this->assertEquals($dates[1], $nextDates[1]->start());
    }

    public function test_can_generate_between_dates()
    {
        $nextDates = $this->event->datesBetween(carbon('2019-11-23'), carbon('2019-11-26'));

        $dates = collect([
            carbon('2019-11-23 19:00'),
            carbon('2019-11-24 11:00'),
            carbon('2019-11-25 11:00'),
        ]);

        $this->assertCount(3, $nextDates);

        $this->assertEquals($dates[0], $nextDates[0]->start());
        $this->assertEquals($dates[1], $nextDates[1]->start());
        $this->assertEquals($dates[2], $nextDates[2]->start());

        $dates = collect(
            [
                carbon('2019-11-24 11:00'),
                carbon('2019-11-25 11:00'),
            ]
        );

        $nextDates = $this->event->datesBetween(carbon('2019-11-24'), carbon('2019-11-26'));

        $this->assertEquals($dates[0], $nextDates[0]->start());
        $this->assertEquals($dates[1], $nextDates[1]->start());

        $this->assertEmpty(
            $this->event->datesBetween(carbon('2019-11-26'), carbon('2019-11-28'))
        );
    }

    public function test_can_generate_only_start_date_when_collapsed()
    {
        Carbon::setTestNow(carbon('2019-11-19'));

        $this->allDayEvent->asSingleDay = true;
        $this->event->asSingleDay = true;
        $nextDates = $this->allDayEvent->upcomingDates();

        $dates = collect([
            carbon('2019-11-20'),
        ]);

        $this->assertCount(1, $nextDates);

        $this->assertEquals($dates[0], $nextDates[0]->start());

        $nextDates = $this->event->upcomingDates();

        $dates = collect([
            carbon('2019-11-23 7PM'),
        ]);

        $this->assertCount(1, $nextDates);

        $this->assertEquals($dates[0], $nextDates[0]->start());
    }

    public function test_can_generate_start_date_when_during_and_collapsed()
    {
        Carbon::setTestNow(carbon('2019-11-21 12:00'));

        $this->allDayEvent->asSingleDay = true;
        $nextDates = $this->allDayEvent->upcomingDates();

        $dates = collect([
            carbon('2019-11-20'),
        ]);

        $this->assertCount(1, $nextDates);

        $this->assertEquals($dates[0], $nextDates[0]->start());
    }

    public function test_exposes_end_date_when_collapsed()
    {
        Carbon::setTestNow(carbon('2019-11-21 12:00'));

        $this->event->asSingleDay = true;

        $events = new Events();

        $events->add($this->event);

        $nextDates = $this->event->upcomingDates();

        $this->assertCount(1, $nextDates);
        $this->assertEquals('2019-11-25', $nextDates[0]->endDate());

        $next = $events->upcoming(1);
    }

    public function test_can_get_all_events_when_during()
    {
        Carbon::setTestNow(carbon('2019-11-24'));
        $event = EventFactory::createFromArray(
            [
                'multi_day' => true,
                'days' => [
                    [
                        'date' => '2019-11-23',
                        'start_time' => '19:00',
                        'end_time' => '21:00',
                    ],
                    [
                        'date' => '2019-11-25',
                        'start_time' => '11:00',
                        'end_time' => '15:00',
                    ],
                    [
                        'date' => '2019-11-26',
                        'start_time' => '11:00',
                        'end_time' => '15:00',
                    ],
                ],
            ]
        );

        $events = new Events();

        $events->add($event);

        $nextDates = $events->all(Carbon::now(), Carbon::now()->addDays(8))
            ->groupBy(function ($event, $key) {
                return $event->start_date;
            })
            ->map(function ($days, $key) {
                return [
                    'date' => $key,
                    'dates' => $days->toArray(),
                ];
            });

        $this->assertCount(2, $nextDates);
    }
}
