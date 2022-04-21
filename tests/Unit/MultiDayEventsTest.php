<?php

namespace TransformStudios\Events\Tests\Unit;

use Carbon\Carbon;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events;
use TransformStudios\Events\Tests\TestCase;
use TransformStudios\Events\Types\MultiDayEvent;

class MultiDayEventsTest extends TestCase
{
    /** @var MultiDayEvent */
    private $allDayEvent;

    /** @var MultiDayEvent */
    private $event;

    /** @var MultiDayEvent */
    private $noEndTimeEvnt;

    public function setUp(): void
    {
        parent::setUp();

        $entry = Entry::make()
            ->slug('multi-day-event')
            ->collection('events')
            ->data([
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
            ]);

        $this->event = EventFactory::createFromEntry($entry);

        $noEndTimeEntry = Entry::make()
            ->collection('events')
            ->slug('no-end-time')
            ->data([
                'multi_day' => true,
                'days' => [
                    [
                        'date' => '2019-11-23',
                        'start_time' => '19:00',
                    ],
                    [
                        'date' => '2019-11-24',
                        'start_time' => '15:00',
                    ],
                ],
            ]);

        $this->noEndTimeEvent = EventFactory::createFromEntry($noEndTimeEntry);

        $allDayEntry = Entry::make()
            ->collection('events')
            ->data([
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
            ]);
        $this->allDayEvent = EventFactory::createFromEntry($allDayEntry);
    }

    /** @test */
    public function canCreateMultiDayEvent()
    {
        $this->assertTrue($this->event instanceof MultiDayEvent);
        $this->assertTrue($this->allDayEvent instanceof MultiDayEvent);
        $this->assertTrue($this->noEndTimeEvent instanceof MultiDayEvent);
        $this->assertTrue($this->event->isMultiDay());
        $this->assertTrue($this->allDayEvent->isMultiDay());
        $this->assertTrue($this->noEndTimeEvent->isMultiDay());
    }

    /** @test */
    public function canGetStart()
    {
        $this->assertEquals(Carbon::parse('2019-11-23 19:00'), $this->event->start());
        $this->assertEquals(Carbon::parse('2019-11-20 0:00'), $this->allDayEvent->start());
    }

    // /** @test */
    // public function canGetEnd()
    // {
    //     $this->assertEquals(Carbon::parse('2019-11-25 15:00'), $this->event->end());
    //     $this->assertEquals(Carbon::parse('2019-11-21')->endOfDay(), $this->allDayEvent->end());
    //     $this->assertEquals(Carbon::parse('2019-11-24')->endOfDay(), $this->noEndTimeEvent->end());
    // }

    /** @test */
    public function noOccurrencesIfNowAfterEndDate()
    {
        Carbon::setTestNow('2019-11-26');
        $this->assertEmpty($this->event->nextOccurrences(1));
    }

    /** @test */
    public function canGenerateNextOccurrenceIfBefore()
    {
        Carbon::setTestNow('2019-11-22');

        $this->assertEquals(
            Carbon::parse('2019-11-23')->setTimeFromTimeString('19:00:00'),
            $this->event->nextOccurrences()[0]->start
        );
        $this->assertEquals(
            Carbon::parse('2019-11-23')->setTimeFromTimeString('21:00'),
            $this->event->nextOccurrences()[0]->end
        );
    }

    /** @test */
    public function canGenerateNextOccurrenceIfDuring()
    {
        Carbon::setTestNow('2019-11-24 10:00');
        $this->assertEquals(
            Carbon::parse('2019-11-24')->setTimeFromTimeString('11:00:00'),
            $this->event->nextOccurrences()[0]->start
        );
    }

    /** @test */
    public function canGenerateICalendar()
    {
        $events = $this->event->toICalendarEvents();

        dd($events);
    }
    /*
        public function test_can_generate_next_dates_when_before_start()
        {
            $dates = collect([
                Carbon::parse('2019-11-23 19:00'),
                Carbon::parse('2019-11-24 11:00'),
            ]);

            Carbon::setTestNow(Carbon::parse('2019-11-23'));

            $nextDates = $this->event->upcomingDates();

            $this->assertCount(2, $nextDates);

            $this->assertEquals($dates[0], $nextDates[0]->start());
            $this->assertEquals($dates[1], $nextDates[1]->start());
        }

        public function test_can_generate_next_dates_when_during()
        {
            $dates = collect([
                Carbon::parse('2019-11-24 11:00'),
                Carbon::parse('2019-11-25 11:00'),
            ]);

            Carbon::setTestNow(Carbon::parse('2019-11-24'));

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
            $nextDates = $this->event->datesBetween(Carbon::parse('2019-11-23'), Carbon::parse('2019-11-26'));

            $dates = collect([
                Carbon::parse('2019-11-23 19:00'),
                Carbon::parse('2019-11-24 11:00'),
                Carbon::parse('2019-11-25 11:00'),
            ]);

            $this->assertCount(3, $nextDates);

            $this->assertEquals($dates[0], $nextDates[0]->start());
            $this->assertEquals($dates[1], $nextDates[1]->start());
            $this->assertEquals($dates[2], $nextDates[2]->start());

            $dates = collect(
                [
                    Carbon::parse('2019-11-24 11:00'),
                    Carbon::parse('2019-11-25 11:00'),
                ]
            );

            $nextDates = $this->event->datesBetween(Carbon::parse('2019-11-24'), Carbon::parse('2019-11-26'));

            $this->assertEquals($dates[0], $nextDates[0]->start());
            $this->assertEquals($dates[1], $nextDates[1]->start());

            $this->assertEmpty(
                $this->event->datesBetween(Carbon::parse('2019-11-26'), Carbon::parse('2019-11-28'))
            );
        }

        public function test_can_generate_only_start_date_when_collapsed()
        {
            Carbon::setTestNow(Carbon::parse('2019-11-19'));

            $this->allDayEvent->asSingleDay = true;
            $this->event->asSingleDay = true;
            $nextDates = $this->allDayEvent->upcomingDates();

            $dates = collect([
                Carbon::parse('2019-11-20'),
            ]);

            $this->assertCount(1, $nextDates);

            $this->assertEquals($dates[0], $nextDates[0]->start());

            $nextDates = $this->event->upcomingDates();

            $dates = collect([
                Carbon::parse('2019-11-23 7PM'),
            ]);

            $this->assertCount(1, $nextDates);

            $this->assertEquals($dates[0], $nextDates[0]->start());
        }

        public function test_can_generate_start_date_when_during_and_collapsed()
        {
            Carbon::setTestNow(Carbon::parse('2019-11-21 12:00'));

            $this->allDayEvent->asSingleDay = true;
            $nextDates = $this->allDayEvent->upcomingDates();

            $dates = collect([
                Carbon::parse('2019-11-20'),
            ]);

            $this->assertCount(1, $nextDates);

            $this->assertEquals($dates[0], $nextDates[0]->start());
        }

        public function test_exposes_end_date_when_collapsed()
        {
            Carbon::setTestNow(Carbon::parse('2019-11-21 12:00'));

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
            Carbon::setTestNow(Carbon::parse('2019-11-24'));
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
        */
}
