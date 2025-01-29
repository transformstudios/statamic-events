<?php

namespace TransformStudios\Events\Tests\Unit;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Extensions\Pagination\LengthAwarePaginator;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events;
use TransformStudios\Events\Tests\TestCase;

class EventsTest extends TestCase
{
    #[Test]
    public function can_generate_dates_when_now_before_start()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->collection('events')
            ->slug('recurring-event')
            ->data([
                'title' => 'Recurring Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'end_date' => Carbon::now()->addDays(2)->toDateString(),
                'recurrence' => 'daily',
            ])->save();

        $event = tap(Entry::make()
            ->collection('events')
            ->slug('single-event')
            ->data([
                'title' => 'Single Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '13:00',
            ]))->save();

        $occurrences = Events::fromCollection(handle: 'events')
            ->between(now(), now()->addDays(2)->endOfDay());

        $expectedStartDates = [
            now()->setTimeFromTimeString('11:00'),
            now()->setTimeFromTimeString('13:00'),
            now()->addDay()->setTimeFromTimeString('11:00'),
            now()->addDays(2)->setTimeFromTimeString('11:00'),
        ];
        $this->assertCount(4, $occurrences);

        $this->assertEquals($expectedStartDates[0], $occurrences[0]->start);
        $this->assertEquals($expectedStartDates[1], $occurrences[1]->start);
        $this->assertEquals($expectedStartDates[2], $occurrences[2]->start);
        $this->assertEquals($expectedStartDates[3], $occurrences[3]->start);
    }

    #[Test]
    public function can_paginate_upcoming_occurrences()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->collection('events')
            ->slug('recurring-event')
            ->data([
                'title' => 'Recurring Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ])->save();

        $occurrences = Events::fromCollection(handle: 'events')
            ->upcoming(10);

        $this->assertCount(10, $occurrences);
        $paginator = Events::fromCollection(handle: 'events')
            ->pagination(perPage: 2)
            ->upcoming(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertCount(2, $occurrences = $paginator->items());
        $this->assertEquals(now()->addDay()->setTimeFromTimeString('11:00'), $paginator->items()[1]->start);

        $paginator = Events::fromCollection(handle: 'events')
            ->pagination(perPage: 3, page: 3)
            ->upcoming(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertCount(3, $occurrences = $paginator->items());
        $this->assertEquals(3, $paginator->currentPage());
    }

    #[Test]
    public function can_paginate_occurrences_between()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->collection('events')
            ->slug('recurring-event')
            ->data([
                'title' => 'Recurring Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ])->save();

        $occurrences = Events::fromCollection(handle: 'events')
            ->between(now(), now()->addDays(9)->endOfDay());

        $this->assertCount(10, $occurrences);
        $paginator = Events::fromCollection(handle: 'events')
            ->pagination(perPage: 2)
            ->between(now(), now()->addDays(9)->endOfDay());

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);

        $this->assertCount(2, $occurrences = $paginator->items());

        $this->assertEquals(now()->addDay()->setTimeFromTimeString('11:00'), $paginator->items()[1]->start);
    }

    #[Test]
    public function can_filter_events()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->collection('events')
            ->slug('recurring-event')
            ->data([
                'title' => 'Recurring Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ])->save();

        Entry::make()
            ->collection('events')
            ->slug('other-event')
            ->data([
                'title' => 'Other Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ])->save();

        $occurrences = Events::fromCollection(handle: 'events')
            ->filter('title:contains', 'Other')
            ->between(now(), now()->addDays(9)->endOfDay());

        $this->assertCount(10, $occurrences);
    }

    #[Test]
    public function can_filter_multiple_events()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->collection('events')
            ->slug('recurring-event')
            ->data([
                'title' => 'Recurring Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ])->save();

        Entry::make()
            ->collection('events')
            ->slug('other-event')
            ->data([
                'title' => 'Other Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ])->save();

        Entry::make()
            ->collection('events')
            ->slug('other-event')
            ->data([
                'title' => 'Other Event 2',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'weekly',
            ])->save();

        $occurrences = Events::fromCollection(handle: 'events')
            ->filter('title:contains', 'Other')
            ->filter('recurrence:is', 'daily')
            ->between(now(), now()->addDays(9)->endOfDay());

        $this->assertCount(10, $occurrences);
    }

    #[Test]
    public function can_filter_by_term_events()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->collection('events')
            ->slug('recurring-event')
            ->data([
                'title' => 'Recurring Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
                'categories' => ['one'],
            ])->save();

        Entry::make()
            ->collection('events')
            ->slug('other-event')
            ->data([
                'title' => 'Other Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
                'categories' => ['two'],
            ])->save();

        $occurrences = Events::fromCollection(handle: 'events')
            ->terms('categories::two')
            ->between(now(), now()->addDays(9)->endOfDay());

        $this->assertCount(10, $occurrences);
    }

    #[Test]
    public function can_filter_by_filter_events()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->collection('events')
            ->slug('recurring-event')
            ->data([
                'title' => 'Recurring Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ])->save();

        Entry::make()
            ->collection('events')
            ->slug('other-event')
            ->data([
                'title' => 'Other Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ])->save();

        $occurrences = Events::fromCollection(handle: 'events')
            ->filter('title:contains', 'Recurring')
            ->between(now(), now()->addDays(9)->endOfDay());

        $this->assertCount(10, $occurrences);
    }

    #[Test]
    public function can_determine_occurs_at_for_single_event()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $entry = Entry::make()
            ->collection('events')
            ->slug('single-event')
            ->id('the-id')
            ->data([
                'title' => 'Single Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
            ]);

        $event = EventFactory::createFromEntry($entry);

        $this->assertTrue($event->occursOnDate(now()));
    }

    #[Test]
    public function can_determine_occurs_at_for_multiday_event()
    {
        Carbon::setTestNow(now());

        $entry = Entry::make()
            ->slug('multi-day-event')
            ->collection('events')
            ->data([
                'multi_day' => true,
                'days' => [
                    [
                        'date' => now()->toDateString(),
                        'start_time' => '19:00',
                        'end_time' => '21:00',
                    ],
                    [
                        'date' => now()->addDay()->toDateString(),
                        'start_time' => '11:00',
                        'end_time' => '15:00',
                    ],
                    [
                        'date' => now()->addDays(2)->toDateString(),
                        'start_time' => '11:00',
                        'end_time' => '15:00',
                    ],
                ],
            ]);

        $event = EventFactory::createFromEntry($entry);

        $this->assertFalse($event->occursOnDate(now()->subDay()));
        $this->assertTrue($event->occursOnDate(now()));
        $this->assertTrue($event->occursOnDate(now()->addDay()));
        $this->assertTrue($event->occursOnDate(now()->addDays(2)));
        $this->assertFalse($event->occursOnDate(now()->addDays(3)));
    }

    #[Test]
    public function can_exclude_dates()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->collection('events')
            ->slug('recurring-event')
            ->data([
                'title' => 'Recurring Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
                'exclude_dates' => [['date' => Carbon::now()->addDay()->toDateString()]],
            ])->save();

        $occurrences = Events::fromCollection(handle: 'events')
            ->between(now(), now()->addDays(3)->endOfDay());

        $this->assertCount(3, $occurrences);
    }

    #[Test]
    public function can_handle_empty_exclude_dates()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->collection('events')
            ->slug('recurring-event')
            ->data([
                'title' => 'Recurring Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
                'exclude_dates' => [['id' => 'random-id']],
            ])->save();

        $occurrences = Events::fromCollection(handle: 'events')
            ->between(now(), now()->addDays(3)->endOfDay());

        $this->assertCount(4, $occurrences);
    }

    #[Test]
    public function can_filter_our_events_with_no_start_date()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->collection('events')
            ->slug('single-event')
            ->data([
                'title' => 'Single Event',
                'start_time' => '11:00',
                'end_time' => '12:00',
            ])->save();
        Entry::make()
            ->collection('events')
            ->slug('legacy-multi-day-event')
            ->data([
                'title' => 'Legacy Multi-day Event',
                'multi_day' => true,
                'days' => [
                    ['date' => 'bad-date'],
                ],
            ])->save();
        Entry::make()
            ->collection('events')
            ->slug('legacy-multi-day-event-2')
            ->data([
                'title' => 'Legacy Multi-day Event',
                'multi_day' => true,
            ])->save();

        $occurrences = Events::fromCollection(handle: 'events')
            ->upcoming(5);

        $this->assertEmpty($occurrences);
    }
}
