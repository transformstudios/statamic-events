<?php

namespace TransformStudios\Events\Tests\Unit;

use Illuminate\Support\Carbon;
use Statamic\Extensions\Pagination\LengthAwarePaginator;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events;
use TransformStudios\Events\Tests\TestCase;

class EventsTest extends TestCase
{
    /** @test */
    public function canGenerateDatesWhenNowBeforeStart()
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

        Entry::make()
            ->collection('events')
            ->slug('single-event')
            ->data([
                'title' => 'Single Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '13:00',
            ])->save();

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

    /** @test */
    public function canPaginateUpcomingOccurrences()
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

    /** @test */
    public function canPaginateOccurrencesBetween()
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

    /** @test */
    public function canFilterEvents()
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
            ->blueprint($this->blueprint->handle())
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

    /** @test */
    public function canFilterMultipleEvents()
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
            ->blueprint($this->blueprint->handle())
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

    /** @test */
    public function canFilterByTermEvents()
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

    /** @test */
    public function canFilterByFilterEvents()
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

    /** @test */
    public function canDetermineOccursAtForSingleEvent()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $entry = Entry::make()
            ->blueprint($this->blueprint->handle())
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

    /** @test */
    public function canDetermineOccursAtForMultidayEvent()
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

    /** @test */
    public function canExcludeDates()
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
            ->between(now(), now()->addDays(9)->endOfDay());

        $this->assertCount(9, $occurrences);
    }
}
