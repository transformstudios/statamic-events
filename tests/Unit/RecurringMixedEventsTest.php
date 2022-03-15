<?php

namespace TransformStudios\Events\Tests\Unit;

use Carbon\Carbon;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events;

class RecurringMixedEventsTest extends TestCase
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
    /*
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

        Carbon::setTestNow(Carbon::now()->setTimeFromTimeString('14:00:00'));

        $nextEvent = $this->events->upcoming(1);

        $this->assertEquals(
            Carbon::now()->setTimeFromTimeString('13:00:00'),
            $nextEvent->start()
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
*/
}
