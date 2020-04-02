<?php

use Carbon\Carbon;
use Statamic\Testing\TestCase;
use Statamic\Addons\Events\Events;
use Statamic\Addons\Events\EventFactory;
use Statamic\Addons\Events\Types\MultiDayEvent;

class EventsTest extends TestCase
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

    public function test_can_generate_next_dates_when_before_start()
    {
        $dates = [
            carbon('2019-11-20'),
            carbon('2019-11-21'),
            carbon('2019-11-23 19:00'),
        ];

        $events = new Events();

        $events->add($this->event);
        $events->add($this->allDayEvent);

        Carbon::setTestNow(carbon('2019-11-19'));

        $nextDates = $events->upcoming(3);

        $this->assertCount(3, $nextDates);

        $this->assertEquals($dates[0], carbon($nextDates[0]->start_date)->setTimeFromTimeString($nextDates[0]->start_time));
        $this->assertEquals($dates[1], carbon($nextDates[1]->start_date)->setTimeFromTimeString($nextDates[1]->start_time));
        $this->assertEquals($dates[2], carbon($nextDates[2]->start_date)->setTimeFromTimeString($nextDates[2]->start_time));

        $events = new Events();
        $events->add($this->event);
        $events->add(EventFactory::createFromArray(
            [
                'start_date' => '2019-11-27',
                'start_time' => '11:00',
                'end_time' => '12:00',
            ]
        ));

        $dates = [
            carbon('2019-11-23 19:00'),
            carbon('2019-11-24 11:00'),
            carbon('2019-11-25 11:00'),
            carbon('2019-11-27 11:00'),
        ];

        $nextDates = $events->upcoming(4);

        $this->assertCount(4, $nextDates);

        $this->assertEquals($dates[0], carbon($nextDates[0]->start_date)->setTimeFromTimeString($nextDates[0]->start_time));
        $this->assertEquals($dates[1], carbon($nextDates[1]->start_date)->setTimeFromTimeString($nextDates[1]->start_time));
        $this->assertEquals($dates[2], carbon($nextDates[2]->start_date)->setTimeFromTimeString($nextDates[2]->start_time));
        $this->assertEquals($dates[3], carbon($nextDates[3]->start_date)->setTimeFromTimeString($nextDates[3]->start_time));

        $events = new Events();

        $event = EventFactory::createFromArray(
            [
                'start_date' => '2019-11-21',
                'start_time' => '11:00',
                'end_time' => '12:00',
            ]
        );

        $this->event->asSingleDay = true;
        $event->asSingleDay = true;
        $events->add($this->event);
        $events->add($event);

        $dates = [
            carbon('2019-11-21 11:00'),
            carbon('2019-11-23 19:00'),
        ];

        $nextDates = $events->upcoming(4);

        $this->assertCount(2, $nextDates);

        $this->assertEquals($dates[0], carbon($nextDates[0]->start_date)->setTimeFromTimeString($nextDates[0]->start_time));
        $this->assertEquals($dates[1], carbon($nextDates[1]->start_date)->setTimeFromTimeString($nextDates[1]->start_time));
    }

    public function test_empty_collection_when_after_end()
    {
        $events = new Events();

        $events->add($this->event);

        Carbon::setTestNow(carbon('2019-11-26'));

        $nextDates = $events->upcoming(2);

        $this->assertCount(0, $nextDates);
    }

    public function test_event_pagination()
    {
        $events = new Events();

        $events->add($this->event);
        $events->add($this->allDayEvent);

        Carbon::setTestNow(carbon('2019-11-19'));

        $nextDates = $this->event->upcomingDates(2, 1);
        $this->assertCount(2, $nextDates);

        $this->assertEquals(Carbon::parse('2019-11-24 11:00'), $nextDates[0]->start());
        $this->assertEquals(Carbon::parse('2019-11-25 11:00'), $nextDates[1]->start());

        $nextDates = $events->upcoming(2, 2);

        $this->assertCount(2, $nextDates);

        $this->assertEquals(
            Carbon::parse('2019-11-23 19:00'),
            carbon($nextDates[0]->start_date . ' ' . $nextDates[0]->start_time)
        );

        $this->assertEquals(
            Carbon::parse('2019-11-24 11:00'),
            carbon($nextDates[1]->start_date . ' ' . $nextDates[1]->start_time)
        );
    }
}
