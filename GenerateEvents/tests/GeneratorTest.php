<?php

use Carbon\Carbon;
use Statamic\API\URL;
use Statamic\API\Cache;
use Statamic\API\Entry;
use Statamic\API\Config;
use Statamic\API\Stache;
use Statamic\API\Collection;
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

        if ($collection = Collection::whereHandle('test-events')) {
            $collection->delete();
        }
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

    public function test_can_generate_next_day_if_before()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => $startDate,
            'recurrence' => 'daily',
        ];

        $nextDate = $this->generator->nextDate($event, Carbon::now()->subDays(5));

        $this->assertEquals($startDate, $nextDate);

        $nextDate = $this->generator->nextDate($event, Carbon::now()->setTimeFromTimeString('10:59:00'));

        $this->assertEquals($startDate, $nextDate);
    }

    public function test_can_generate_next_day_if_after()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => $startDate,
            'recurrence' => 'daily',
        ];

        Carbon::setTestNow($startDate->copy()->addMinutes(1));
        $nextDate = $this->generator->nextDate($event, Carbon::now()->addHour());

        $this->assertEquals($startDate->addDay(), $nextDate);
    }

    public function test_returns_null_if_after_non_recurring()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => $startDate,
            'recurrence' => 'none',
        ];

        Carbon::setTestNow($startDate->copy()->addMinutes(1));
        $nextDate = $this->generator->nextDate($event, Carbon::now()->addHour());

        $this->assertNull($nextDate);

        $this->generator->add($event);

        $shouldBeEmpty = $this->generator->all(Carbon::now()->addDay(), Carbon::now()->addDays(4));

        $this->assertEmpty($shouldBeEmpty);
    }

    public function test_can_generate_next_weekly_date_when_before()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => $startDate,
            'recurrence' => 'weekly',
        ];

        $nextDate = $this->generator->nextDate($event, Carbon::now()->subDays(3));
        $this->assertEquals($startDate, $nextDate);

        $nextDate = $this->generator->nextDate($event, Carbon::now()->subWeeks(1)->subDays(3));
        $this->assertEquals($startDate, $nextDate);
    }

    public function test_can_generate_next_weekly_date_when_after()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => $startDate,
            'recurrence' => 'weekly',
        ];

        $nextDate = $this->generator->nextDate($event, Carbon::now()->addDays(3));

        $this->assertEquals($startDate->addWeek(), $nextDate);
    }

    public function test_can_generate_next_weekly_date_when_a_week_after()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => $startDate,
            'recurrence' => 'weekly',
        ];

        $nextDate = $this->generator->nextDate($event, Carbon::now()->addWeek());

        $this->assertEquals($startDate->addWeeks(2), $nextDate);
    }

    public function test_can_generate_next_monthly_date_if_before()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => $startDate,
            'recurrence' => 'monthly',
        ];

        $nextDate = $this->generator->nextDate($event, $startDate->copy()->subDays(5));
        $this->assertEquals($startDate, $nextDate);

        $nextDate = $this->generator->nextDate($event, $startDate->copy()->subMonths(2)->subDay(5));
        $this->assertEquals($startDate, $nextDate);
    }

    public function test_can_generate_next_monthly_date_if_after()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => $startDate,
            'recurrence' => 'monthly',
        ];

        $nextDate = $this->generator->nextDate($event, $startDate->copy()->addDays(5));
        $this->assertEquals(
            $startDate->copy()->addMonths(1),
            $nextDate
        );

        $nextDate = $this->generator->nextDate($event, $startDate->copy()->addMonths(1)->subDays(5));
        $this->assertEquals(
            $startDate->copy()->addMonths(1),
            $nextDate
        );

        $nextDate = $this->generator->nextDate(
            $event,
            $startDate->copy()->addYears(1)->addMonths(1)->addDays(5)
        );
        $this->assertEquals(
            $startDate->copy()->addYears(1)->addMonths(2),
            $nextDate
        );
    }

    public function test_can_generate_next_x_dates_from_today_before_event_time()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');
        $event = [
            'start_date' => $startDate,
            'recurrence' => 'daily',
        ];

        for ($x = 0;$x < 2;$x++) {
            $events[$x] = $event;
            $events[$x]['next_date'] = $startDate->copy()->addDays($x)->toString();
        }

        $this->generator->add($event);

        Carbon::setTestNow($startDate->copy()->subMinutes(1));

        $nextDates = $this->generator->nextXOccurrences(2);

        $this->assertEquals($events, $nextDates->toArray());
    }

    public function test_can_generate_next_x_dates_from_today()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');
        $event = [
            'start_date' => $startDate,
            'recurrence' => 'daily',
        ];

        for ($x = 0;$x < 3;$x++) {
            $events[$x] = $event;
            $events[$x]['next_date'] = $startDate->copy()->addDays($x + 1)->toString();
        }

        $this->generator->add($event);

        Carbon::setTestNow($startDate->copy()->addMinutes(1));

        $nextDates = $this->generator->nextXOccurrences(3);

        $this->assertEquals($events, $nextDates->toArray());
    }

    public function test_generates_all_occurrences_when_daily_after_start_date()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => $startDate->copy()->addDay()->toString(),
            'end_date' => $startDate->copy()->addDays(3),
            'recurrence' => 'daily',
        ];

        for ($x = 2;$x < 4;$x++) {
            $events[$x - 2] = $event;
            $events[$x - 2]['next_date'] = $startDate->copy()->addDays($x)->toString();
        }

        $this->generator->add($event);

        Carbon::setTestNow($startDate->copy()->addDays(1));
        $nextEvents = $this->generator->nextXOccurrences(3);

        $this->assertCount(2, $nextEvents);

        $this->assertEquals($events, $nextEvents->toArray());
    }

    public function test_generates_all_occurrences_when_weekly_after_start_date()
    {
        $startDate = Carbon::now()->subDay()->setTimeFromTimeString('11:00:00');

        $event = [
            'start_date' => $startDate->toString(),
            'end_date' => $startDate->copy()->addWeeks(3)->toString(),
            'recurrence' => 'weekly',
        ];

        for ($x = 1;$x <= 3;$x++) {
            $events[$x - 1] = $event;
            $events[$x - 1]['next_date'] = $startDate->copy()->addWeeks($x)->toString();
        }

        $this->generator->add($event);

        $nextEvents = $this->generator->nextXOccurrences(4);

        $this->assertCount(3, $nextEvents);

        $this->assertEquals($events, $nextEvents->toArray());
    }

    public function test_generates_next_occurrence_when_multiple_events()
    {
        $this->generator->add([
            'start_date' => Carbon::now()->subDays(8)->setTimeFromTimeString('11:00:00'),
            'duration' => 1,
            'recurrence' => 'weekly',
            'end_date' => Carbon::now()->addWeeks(3)->setTimeFromTimeString('11:00:00'),
        ]);

        $this->generator->add([
            'start_date' => Carbon::now()->subDays(2)->setTimeFromTimeString('13:00:00'),
            'duration' => '2',
            'recurrence' => 'daily',
            'end_date' => Carbon::now()->addDays(5)->setTimeFromTimeString('13:00:00'),
        ]);

        $nextEvent = $this->generator->nextXOccurrences(1);

        Carbon::setTestNow(Carbon::now()->setTimeFromTimeString('14:00:00'));

        $this->assertArrayHasKey('next_date', $nextEvent);
        $this->assertEquals(
            Carbon::now()->addDays(1)->setTimeFromTimeString('13:00:00'),
            carbon($nextEvent['next_date'])
        );
    }

    public function test_generates_all_occurrences_single_event_from_to()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

        $this->generator->add([
            'id' => 'weekly-event',
            'start_date' => $startDate->copy()->subDays(8)->toString(),
            'duration' => 1,
            'recurrence' => 'weekly',
            'end_date' => $startDate->copy()->addWeeks(3)->toString(),
        ]);

        $from = Carbon::now()->subDays(3);
        $to = $from->copy()->addDays(15);

        $events = $this->generator->all($from, $to);

        $this->assertCount(2, $events);

        $this->assertArraySubset(
            [
                'id' => 'weekly-event',
            ],
            $events[0]
        );

        $this->assertEquals(
            Carbon::now()->subDays(1)->setTimeFromTimeString('11:00:00'),
            carbon($events[0]['next_date'])
        );
        $this->assertEquals(
            Carbon::now()->addDays(6)->setTimeFromTimeString('11:00:00'),
            carbon($events[1]['next_date'])
        );
    }

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

        $from = Carbon::now()->subDays(3);
        $to = $from->copy()->addDays(15);

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

    public function test_generates_paginated_events_from_controller()
    {
        $this->withoutMiddleware();
        $this->withoutEvents();

        $collection = Collection::create('test-events');

        $collection->save();

        $startDate = Carbon::now()->setTimeFromTimeString('13:00:00');

        $entry = Entry::create('event-one')
            ->collection('test-events')
            ->with([
                'start_date' => $startDate->copy()->subDays(2)->toString(),
                'recurrence' => 'daily',
                'end_date' => $startDate->copy()->addDays(5)->toString(),
            ])
            ->get();

        $entry->save();

        $collection->addEntry($entry->id(), $entry);

        // $repo = app('stache')->repo('collections');
        // $repo->setItem('test-events', $collection);

        // $repo = app('stache')->repo('entries');
        // $repo->setItem('entries::' . $entry->id(), $entry);

//        dd(Entry::whereCollection('test-events'));

        // // workaround Statamic bug: https://github.com/statamic/v2-hub/issues/2363
        // $repo = app('stache')->repo('users');
        // $repo->setItem($this->user->id(), $this->user);
        // // end workaround

        // Cache::clear();

        $response = $this->get(
            URL::assemble(
                Config::getSiteUrl(),
                '!/GenerateEvents/next/?' . http_build_query(
                    [
                        'collection' => 'test-events',
                        'limit' => 2,
                        'offset' => 2,
                    ]
                )
            )
        );

        $response->assertResponseStatus(201);

        // $subscriptions = $this->paymentGateway->subscriptions();

        // $this->assertCount(1, $subscriptions);
    }
}
