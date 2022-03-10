<?php

namespace TransformStudios\Events\Tests;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Types\RecurringEvent;

class RecurringEveryXEventsTest extends TestCase
{
    /** @test */
    public function canCreateEveryXEvent()
    {
        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'weeks',
            ]);

        $event = EventFactory::createFromEntry($recurringEntry);

        $this->assertInstanceOf(RecurringEvent::class, $event);
    }

    /** @test */
    public function endDateNullIfNoEndDate()
    {
        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'weeks',
            ]);

        $event = EventFactory::createFromEntry($recurringEntry);

        $this->assertNull($event->end());
    }

    /** @test */
    public function noOccurencesWhenNowAfterEndDate()
    {
        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'end_date' => Carbon::now()->addDays(2)->toDateString(),
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'days',
            ]);

        $event = EventFactory::createFromEntry($recurringEntry);

        Carbon::setTestNow(now()->addDays(3));
        $nextOccurrences = $event->nextOccurrences();

        $this->assertEmpty($nextOccurrences);
    }

    /** @test */
    public function canGenerateOccurrenceIfNowBefore()
    {
        $startDate = Carbon::now()->addDay()->setTimeFromTimeString('11:00');
        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->data([
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'days',
            ]);

        $occurrences = EventFactory::createFromEntry($recurringEntry)
            ->nextOccurrences(1);

        $this->assertCount(1, $occurrences);

        $this->assertEquals($startDate, $occurrences[0]->augmentedValue('start'));

        Carbon::setTestNow(now()->setTimeFromTimeString('10:59:59'));
        $occurrences = EventFactory::createFromEntry($recurringEntry)
            ->nextOccurrences(1);

        $this->assertEquals($startDate, $occurrences[0]->augmentedValue('start'));
    }

    /** @test */
    public function canGenerateOccurrenceIfDuring()
    {
        $startDate = Carbon::now()->setTimeFromTimeString('11:00');
        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->data([
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'days',
            ]);

        Carbon::setTestNow(now()->addMinutes(10));
        $occurrences = EventFactory::createFromEntry($recurringEntry)
            ->nextOccurrences(1);

        $this->assertEquals($startDate, $occurrences[0]->augmentedValue('start'));
    }

    /** @test */
    public function canGenerateOccurrenceIfNowAfterFirstDate()
    {
        $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00:00');

        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->data([
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'days',
            ]);

        Carbon::setTestNow($startDate->addMinutes(1)->addHour());

        $event = EventFactory::createFromEntry($recurringEntry);

        $occurrences = $event->nextOccurrences(1);

        $this->assertEquals($startDate->addDays(2), $occurrences[0]->augmentedValue('start'));

        // $nextDate = $event->upcomingDate(Carbon::now()->addDays(2));

        // $this->assertEquals($startDate, $nextDate->start());
    }

    /** @test */
    public function canGenerateNextOccurrenceInWeeksIfNowAfterStart()
    {
        $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00:00');

        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->data([
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'weeks',
            ]);

        Carbon::setTestNow($startDate->addHours(2));

        $event = EventFactory::createFromEntry($recurringEntry);

        $occurrences = $event->nextOccurrences(1);

        $this->assertEquals($startDate->addWeeks(2), $occurrences[0]->augmentedValue('start'));
    }

    /** @test */
    public function canGenerateNextOccurrenceIfNow_after_weeks()
    {
        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->data([
                'start_date' => '2021-01-18',
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'weeks',
            ]);

        $event = EventFactory::createFromEntry($recurringEntry);

        Carbon::setTestNow(Carbon::parse('2021-03-04')->setTimeFromTimeString('11:00:00'));

        $occurrences = $event->nextOccurrences(1);

        $this->assertNotEmpty($occurrences);

        $this->assertEquals(Carbon::parse('2021-03-15')->setTimeFromTimeString('11:00:00'), $occurrences[0]->augmentedValue('start'));
    }

    /** @test */
    public function canGenerateNextOccurrenceIfNowDuringMonths()
    {
        $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00:00');

        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->data([
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'months',
            ]);

        Carbon::setTestNow($startDate->addMinutes(1));

        $event = EventFactory::createFromEntry($recurringEntry);

        $occurrences = $event->nextOccurrences(1);

        $this->assertEquals($startDate->setTimeFromTimeString('11:00:00'), $occurrences[0]->augmentedValue('start'));
    }

    /** @test */
    public function canGenerateNextXOccurrencesFromTodayBeforeEventTime()
    {
        $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00:00');
        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->data([
                    'start_date' => $startDate->toDateString(),
                    'start_time' => '11:00',
                    'end_time' => '12:00',
                    'recurrence' => 'every',
                    'interval' => 2,
                    'period' => 'days',
            ]);
        $event = EventFactory::createFromEntry($recurringEntry);

        for ($x = 0; $x < 2; $x++) {
            $events[] = $startDate->addDays($x * 2);
        }

        Carbon::setTestNow($startDate->subMinutes(1));

        $occurrences = $event->nextOccurrences(2);

        $this->assertCount(2, $occurrences);

        $this->assertEquals($events[0], $occurrences[0]->augmentedValue('start'));
        $this->assertEquals($events[1], $occurrences[1]->augmentedValue('start'));
    }

    /** @test */
    public function canGenerateAllOccurrencesWhenAfterStartDateDaily()
    {
        $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00:00');

        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->data([
                'start_date' => $startDate->addDay()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'end_date' => $startDate->addDays(5)->toDateString(),
                'recurrence' => 'every',
                'interval' => 2,
                'period' => 'days',
        ]);

        for ($x = 1; $x <= 2; $x++) {
            $events[] = $startDate->addDays($x * 2 + 1);
        }

        $event = EventFactory::createFromEntry($recurringEntry);

        Carbon::setTestNow($startDate->addDays(1)->addHour(2));
        $occurrences = $event->nextOccurrences(5);

        $this->assertCount(2, $occurrences);

        $this->assertEquals($events[0], $occurrences[0]->augmentedValue('start'));
        $this->assertEquals($events[1], $occurrences[1]->augmentedValue('start'));
    }
    /*
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
*/
}
