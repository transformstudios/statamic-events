<?php

use Carbon\Carbon;
use Statamic\Testing\TestCase;
use Statamic\Addons\GenerateEvents\Generator;

class GeneratorTest extends TestCase
{
    /** @var Generator */
    private $generator;

    public function setUp()
    {
        parent::setUp();

        $this->generator = new Generator();
    }

    // public function test_can_generate_next_day_if_after()
    // {
    //     $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

    //     $this->generator->add([
    //         'start_date' => $startDate,
    //         'recurrence' => 'daily',
    //     ]);

    //     $nextEvent = $this->generator->nextEvent($startDate->copy()->addHour());

    //     $this->assertArrayHasKey('next_date', $nextEvent);

    //     $this->assertEquals($startDate->addDay(), carbon($nextEvent['next_date']));
    // }

    // public function test_can_generate_next_day_if_before()
    // {
    //     $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

    //     $this->generator->add([
    //         'start_date' => $startDate,
    //         'recurrence' => 'daily',
    //     ]);

    //     $nextEvent = $this->generator->nextEvent(Carbon::now()->subDays(5));

    //     $this->assertEquals($startDate, carbon($nextEvent['next_date']));
    // }

    // public function test_can_generate_next_weekly_date_when_after()
    // {
    //     $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

    //     $this->generator->add([
    //         'start_date' => $startDate,
    //         'recurrence' => 'weekly',
    //     ]);

    //     $nextEvent = $this->generator->nextEvent(Carbon::now()->addDays(3));

    //     $this->assertEquals($startDate->addWeek(), carbon($nextEvent['next_date']));
    // }

    // public function test_can_generate_next_weekly_date_when_a_week_after()
    // {
    //     $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

    //     $this->generator->add([
    //         'start_date' => $startDate,
    //         'recurrence' => 'weekly',
    //     ]);

    //     $nextEvent = $this->generator->nextEvent(Carbon::now()->addWeek());

    //     $this->assertEquals($startDate->addWeeks(2), carbon($nextEvent['next_date']));
    // }

    // public function test_can_generate_next_weekly_date_when_before()
    // {
    //     $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

    //     $this->generator->add([
    //         'start_date' => $startDate,
    //         'recurrence' => 'weekly',
    //     ]);

    //     $nextEvent = $this->generator->nextEvent(Carbon::now()->subDays(3));
    //     $this->assertEquals($startDate, carbon($nextEvent['next_date']));

    //     $nextEvent = $this->generator->nextEvent(Carbon::now()->subWeeks(1)->subDays(3));
    //     $this->assertEquals($startDate, carbon($nextEvent['next_date']));
    // }

    // public function test_can_generate_next_monthly_date_if_after()
    // {
    //     $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

    //     $this->generator->add([
    //         'start_date' => $startDate,
    //         'recurrence' => 'monthly',
    //     ]);

    //     $nextEvent = $this->generator->nextEvent($startDate->copy()->addDays(5));
    //     $this->assertEquals(
    //         $startDate->copy()->addMonths(1),
    //         carbon($nextEvent['next_date'])
    //     );

    //     $nextEvent = $this->generator->nextEvent($startDate->copy()->addMonths(1)->subDays(5));
    //     $this->assertEquals(
    //         $startDate->copy()->addMonths(1),
    //         carbon($nextEvent['next_date'])
    //     );

    //     $nextEvent = $this->generator->nextEvent(
    //         $startDate->copy()->addYears(1)->addMonths(1)->addDays(5)
    //     );
    //     $this->assertEquals(
    //         $startDate->copy()->addYears(1)->addMonths(2),
    //         carbon($nextEvent['next_date'])
    //     );
    // }

    // public function test_can_generate_next_monthly_date_if_before()
    // {
    //     $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

    //     $this->generator->add([
    //         'start_date' => $startDate,
    //         'recurrence' => 'monthly',
    //     ]);

    //     $nextEvent = $this->generator->nextEvent($startDate->copy()->subDays(5));
    //     $this->assertEquals($startDate, carbon($nextEvent['next_date']));

    //     $nextEvent = $this->generator->nextEvent($startDate->copy()->subMonths(2)->subDay(5));
    //     $this->assertEquals($startDate, carbon($nextEvent['next_date']));
    // }

    // // public function test_can_generate_next_x_weeks_if_before()
    // // {
    // //     $startDate = Carbon::now();

    // //     $interval = 3;

    // //     $generator = new Generator(clone $startDate, 'every_x_weeks', $interval);

    // //     $nextEvent = $generator->nextOccurrence((clone $startDate)->subWeeks(4));
    // //     $this->assertTrue((clone $startDate)->isSameDay($nextEvent));

    // //     $nextEvent = $generator->nextOccurrence((clone $startDate)->subDays(1));
    // //     $this->assertTrue((clone $startDate)->isSameDay($nextEvent));
    // // }

    // // public function test_can_generate_next_x_weeks_if_after()
    // // {
    // //     $startDate = Carbon::now();

    // //     $interval = 3;

    // //     $generator = new Generator(clone $startDate, 'every_x_weeks', $interval);

    // //     $nextEvent = $generator->nextOccurrence((clone $startDate)->addWeeks(4));
    // //     $this->assertTrue((clone $startDate)->addWeeks($interval * 2)->isSameDay($nextEvent));

    // //     $nextEvent = $generator->nextOccurrence((clone $startDate)->addWeeks(2));
    // //     $this->assertTrue((clone $startDate)->addWeeks($interval)->isSameDay($nextEvent));
    // // }

    // public function test_can_generate_next_x_occurrences_from_before_start_date()
    // {
    //     $startDate = Carbon::now()->second(0);

    //     for ($x = 0;$x < 3;$x++) {
    //         $dates[] = (clone $startDate)->addDays($x);
    //     }

    //     $generator = new Generator(clone $startDate, 'daily');
    //     $nextEvents = $generator->nextOccurrences(3, (clone $startDate)->subDays(1));

    //     $this->assertEquals($dates, $nextEvents);
    // }

    // public function test_generates_all_occurences_when_daily_after_start_date()
    // {
    //     $startDate = Carbon::now()->second(0);

    //     for ($x = 2;$x < 4;$x++) {
    //         $dates[] = (clone $startDate)->addDays($x);
    //     }

    //     $generator = new Generator((clone $startDate)->addDay(), 'daily', (clone $startDate)->addDays(3));

    //     $nextEvents = $generator->nextOccurrences(3, (clone $startDate)->addDays(1));

    //     $this->assertCount(2, $nextEvents);

    //     $this->assertEquals($dates, $nextEvents);
    // }

    // public function test_generates_all_occurences_when_weekly_after_start_date()
    // {
    //     $startDate = Carbon::now()->second(0);

    //     for ($x = 1;$x <= 3;$x++) {
    //         $dates[] = (clone $startDate)->addDay()->addWeeks($x);
    //     }

    //     $generator = new Generator((clone $startDate)->addDay(), 'weekly', (clone $startDate)->addDay()->addWeeks(3));

    //     $nextEvents = $generator->nextOccurrences(4, (clone $startDate)->addDays(2));

    //     $this->assertCount(3, $nextEvents);

    //     $this->assertEquals($dates, $nextEvents);
    // }

    // public function test_generates_next_occurrence_when_multiple_events()
    // {
    //     $this->generator->add([
    //         'start_date' => Carbon::now()->subDays(8)->setTimeFromTimeString('11:00:00'),
    //         'duration' => 1,
    //         'recurrence' => 'weekly',
    //         'end_date' => Carbon::now()->addWeeks(3)->setTimeFromTimeString('11:00:00'),
    //     ]);

    //     $this->generator->add([
    //         'start_date' => Carbon::now()->subDays(2)->setTimeFromTimeString('13:00:00'),
    //         'duration' => '2',
    //         'recurrence' => 'daily',
    //         'end_date' => Carbon::now()->addDays(5)->setTimeFromTimeString('13:00:00'),
    //     ]);

    //     $nextEvent = $this->generator->nextEvent(
    //         Carbon::now()
    //         ->subDays(2)
    //         ->setTimeFromTimeString('1:00:00')
    //     );

    //     $this->assertArrayHasKey('next_date', $nextEvent);
    //     $this->assertEquals(
    //         Carbon::now()->subDays(1)->setTimeFromTimeString('11:00:00'),
    //         carbon($nextEvent['next_date'])
    //     );
    // }

    // public function test_generates_all_occurrences_single_event_from_to()
    // {
    //     $this->generator->add([
    //         'id' => 'weekly-event',
    //         'start_date' => Carbon::now()->subDays(8)->setTimeFromTimeString('11:00:00'),
    //         'duration' => 1,
    //         'recurrence' => 'weekly',
    //         'end_date' => Carbon::now()->addWeeks(3)->setTimeFromTimeString('11:00:00'),
    //     ]);

    //     $from = Carbon::now()->subDays(3)->setTimeFromTimeString('1:00:00');
    //     $to = $from->copy()->addDays(15)->setTimeFromTimeString('23:59:59');

    //     $events = $this->generator->all($from, $to);

    //     $this->assertCount(2, $events);

    //     $this->assertArraySubset(
    //         [
    //             'id' => 'weekly-event',
    //         ],
    //         $events[0]
    //     );

    //     $this->assertEquals(
    //         Carbon::now()->subDays(1)->setTimeFromTimeString('11:00:00'),
    //         carbon($events[0]['next_date'])
    //     );
    //     $this->assertEquals(
    //         Carbon::now()->addDays(6)->setTimeFromTimeString('11:00:00'),
    //         carbon($events[1]['next_date'])
    //     );
    // }

    public function test_generates_all_occurrences_multiple_events_from_to()
    {
        $this->generator->add([
            'id' => 'weekly-event',
            'start_date' => Carbon::now()->subDays(8)->setTimeFromTimeString('11:00:00')->toString(),
            'duration' => 1,
            'recurrence' => 'weekly',
            'end_date' => Carbon::now()->addWeeks(3)->setTimeFromTimeString('11:00:00')->toString(),
        ]);

        $this->generator->add([
            'id' => 'daily-event',
            'start_date' => Carbon::now()->subDays(2)->setTimeFromTimeString('13:00:00')->toString(),
            'duration' => '2',
            'recurrence' => 'daily',
            'end_date' => Carbon::now()->addDays(5)->setTimeFromTimeString('13:00:00')->toString(),
        ]);

        $from = Carbon::now()->subDays(3)->setTimeFromTimeString('1:00:00');
        $to = $from->copy()->addDays(15)->setTimeFromTimeString('23:59:59');

        $events = $this->generator->all($from, $to);

        // weekly has 2
        // daily has 8
        $this->assertCount(10, $events);

        $this->assertArraySubset(
            [
                'id' => 'daily-event',
            ],
            $events[0]
        );

        $this->assertEquals(
            Carbon::now()->subDays(2)->setTimeFromTimeString('13:00:00'),
            carbon($events[0]['next_date'])
        );
    }
}
