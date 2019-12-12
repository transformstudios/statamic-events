<?php

use Carbon\Carbon;
use Statamic\Testing\TestCase;
use Statamic\Addons\Events\Events;
use Statamic\Addons\Events\Types\EventFactory;
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
            carbon('2019-11-23 19:00'),
            carbon('2019-11-24 11:00'),
        ];

        $events = new Events();

        $events->add($this->event);

        Carbon::setTestNow(carbon('2019-11-23'));

        $nextDates = $events->upcoming(2);

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
}
