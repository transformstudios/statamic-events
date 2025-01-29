<?php

namespace TransformStudios\Events\Tests\Unit;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Tests\TestCase;

class RecurringDailyEventsTest extends TestCase
{
    #[Test]
    public function nullNextDateIfNowAfterEndDate()
    {
        $recurringEntry = Entry::make()
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'end_date' => Carbon::now()->addDays(2)->toDateString(),
                'recurrence' => 'daily',
            ]);

        $event = EventFactory::createFromEntry($recurringEntry);

        Carbon::setTestNow(now()->addDays(3));
        $nextOccurrences = $event->nextOccurrences();

        $this->assertEmpty($nextOccurrences);
    }

    #[Test]
    public function canGenerateNextDayIfNowIsBefore()
    {
        $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00');

        $recurringEntry = Entry::make()
            ->collection('events')
            ->data([
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ]);

        $event = EventFactory::createFromEntry($recurringEntry);

        Carbon::setTestNow($startDate->setTimeFromTimeString('10:59:00'));

        $nextOccurrences = $event->nextOccurrences(3);

        $this->assertCount(3, $nextOccurrences);

        $this->assertEquals($startDate, $nextOccurrences->first()->start);
    }

    #[Test]
    public function canGenerateNextOccurrenceIfNowIsDuring()
    {
        $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00');
        $recurringEntry = Entry::make()
            ->collection('events')
            ->data([
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ]);

        Carbon::setTestNow($startDate->addMinutes(10));

        $event = EventFactory::createFromEntry($recurringEntry);
        $nextOccurrences = $event->nextOccurrences();

        $this->assertEquals($startDate, $nextOccurrences[0]->start);
    }

    // public function test_can_generate_next_day_if_after()
    // {
    //     $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00:00');

    //     $event = [
    //         'start_date' => $startDate->toDateString(),
    //         'start_time' => '11:00',
    //         'end_time' => '12:00',
    //         'recurrence' => 'daily',
    //     ];

    //     Carbon::setTestNow($startDate->addMinute());

    //     $event = EventFactory::createFromArray($event);

    //     $nextOccurrences = $event->nextOccurrences(1);

    //     $this->assertEquals($startDate->addDay(), $nextDate->start());
    // }

    // public function test_can_generate_next_x_dates_from_today_before_event_time()
    // {
    //     $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');
    //     $event = EventFactory::createFromArray(
    //         [
    //             'start_date' => $startDate->toDateString(),
    //             'start_time' => '11:00',
    //             'end_time' => '12:00',
    //             'recurrence' => 'daily',
    //         ]
    //     );

    //     for ($x = 0; $x < 2; $x++) {
    //         $events[] = $startDate->copy()->addDays($x);
    //     }

    //     $this->events->add($event);

    //     Carbon::setTestNow($startDate->copy()->subMinutes(1));

    //     $nextDates = $this->events->upcoming(2);

    //     $this->assertCount(2, $nextDates);

    //     $this->assertEquals($events[0], $nextDates[0]->start());
    //     $this->assertEquals($events[1], $nextDates[1]->start());
    // }

    // public function test_can_generate_next_x_dates_from_today()
    // {
    //     $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');
    //     $event = EventFactory::createFromArray([
    //         'start_date' => $startDate->toDateString(),
    //         'start_time' => '11:00',
    //         'end_time' => '12:00',
    //         'recurrence' => 'daily',
    //     ]);

    //     for ($x = 0; $x < 3; $x++) {
    //         $events[] = $startDate->copy()->addDays($x);
    //     }

    //     $this->events->add($event);

    //     Carbon::setTestNow($startDate->copy()->addMinutes(1));

    //     $nextDates = $this->events->upcoming(3);

    //     $this->assertCount(3, $nextDates);

    //     $this->assertEquals($events[0], $nextDates[0]->start());
    //     $this->assertEquals($events[1], $nextDates[1]->start());
    //     $this->assertEquals($events[2], $nextDates[2]->start());
    // }

    // public function test_generates_all_occurrences_when_daily_after_start_date()
    // {
    //     $startDate = Carbon::now()->setTimeFromTimeString('11:00:00');

    //     $event = EventFactory::createFromArray(
    //         [
    //             'start_date' => $startDate->copy()->addDay()->toDateString(),
    //             'start_time' => '11:00',
    //             'end_time' => '12:00',
    //             'end_date' => $startDate->copy()->addDays(3)->toDateString(),
    //             'recurrence' => 'daily',
    //         ]
    //     );

    //     for ($x = 2; $x <= 3; $x++) {
    //         $events[] = $startDate->copy()->addDays($x);
    //     }

    //     $this->events->add($event);

    //     Carbon::setTestNow($startDate->copy()->addDays(1)->addHour(1));
    //     $nextEvents = $this->events->upcoming(3);

    //     $this->assertCount(2, $nextEvents);

    //     $this->assertEquals($events[0], $nextEvents[0]->start());
    //     $this->assertEquals($events[1], $nextEvents[1]->start());
    // }

    // public function test_can_get_last_day_when_before()
    // {
    //     Carbon::setTestNow(Carbon::now()->setTimeFromTimeString('10:30'));

    //     $this->events->add(EventFactory::createFromArray([
    //         'id' => 'daily-event',
    //         'start_date' => Carbon::now()->toDateString(),
    //         'start_time' => '13:00',
    //         'end_time' => '15:00',
    //         'recurrence' => 'daily',
    //         'end_date' => Carbon::now()->addDays(7)->toDateString(),
    //     ]));

    //     $from = Carbon::now()->addDays(7);
    //     $to = Carbon::now()->endOfDay()->addDays(10);

    //     $events = $this->events->all($from, $to);

    //     $this->assertCount(1, $events);
    // }

    // public function test_generates_all_daily_occurrences_single_event_from_to()
    // {
    //     Carbon::setTestNow(Carbon::now()->setTimeFromTimeString('10:30'));

    //     $this->events->add(EventFactory::createFromArray([
    //         'id' => 'daily-event',
    //         'start_date' => Carbon::now()->toDateString(),
    //         'start_time' => '13:00',
    //         'end_time' => '15:00',
    //         'recurrence' => 'daily',
    //         'end_date' => Carbon::now()->addDays(7)->toDateString(),
    //     ]));

    //     $from = Carbon::now()->subDays(1);
    //     $to = Carbon::now()->endOfDay()->addDays(10);

    //     $events = $this->events->all($from, $to);

    //     $this->assertCount(8, $events);
    // }

    // public function test_generates_all_daily_occurrences_single_event_from_to_without_end_date()
    // {
    //     Carbon::setTestNow(Carbon::now()->setTimeFromTimeString('10:30'));

    //     $this->events->add(EventFactory::createFromArray([
    //         'id' => 'daily-event',
    //         'start_date' => Carbon::now()->toDateString(),
    //         'start_time' => '13:00',
    //         'end_time' => '15:00',
    //         'recurrence' => 'daily',
    //     ]));

    //     $from = Carbon::now()->subDays(1);
    //     $to = Carbon::now()->endOfDay()->addDays(10);

    //     $events = $this->events->all($from, $to);

    //     $this->assertCount(11, $events);
    // }

    // public function test_can_exclude_dates()
    // {
    //     Carbon::setTestNow(Carbon::now()->setTimeFromTimeString('10:30'));

    //     $this->events->add(EventFactory::createFromArray([
    //         'id' => 'daily-event',
    //         'start_date' => Carbon::now()->toDateString(),
    //         'start_time' => '13:00',
    //         'end_time' => '15:00',
    //         'recurrence' => 'daily',
    //         'except' => [
    //             ['date' => Carbon::now()->addDays(2)->toDateString()],
    //             ['date' => Carbon::now()->addDays(4)->toDateString()],
    //         ],
    //     ]));

    //     $from = Carbon::now()->subDays(1);
    //     $to = Carbon::now()->endOfDay()->addDays(5);

    //     $events = $this->events->all($from, $to)->toArray();

    //     $this->assertCount(4, $events);

    //     $this->assertEquals(Carbon::now()->toDateString(), $events[0]['start_date']);
    //     $this->assertEquals(Carbon::now()->addDays(1)->toDateString(), $events[1]['start_date']);
    //     $this->assertEquals(Carbon::now()->addDays(3)->toDateString(), $events[2]['start_date']);
    //     $this->assertEquals(Carbon::now()->addDays(5)->toDateString(), $events[3]['start_date']);
    // }
}
