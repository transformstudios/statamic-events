<?php

use Carbon\Carbon;
use Statamic\Testing\TestCase;
use Statamic\Addons\Events\Types\EventFactory;
use Statamic\Addons\Events\Types\MultiDayEvent;

class MultiDayEventsTest extends TestCase
{
    /** @var MultiDayEvent */
    private $event;

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
    }

    public function test_can_get_start()
    {
        $this->assertEquals(carbon('2019-11-23 19:00'), $this->event->start());
    }

    public function test_can_get_end()
    {
        $this->assertEquals(carbon('2019-11-25 15:00'), $this->event->end());
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
}
