<?php

namespace TransformStudios\Events\Tests\Unit;

use Illuminate\Support\Carbon;
use Statamic\Facades\Entry;
use TransformStudios\Events\Events;
use TransformStudios\Events\Tests\TestCase;

class EventsTest extends TestCase
{
    /** @test */
    public function canGenerateDatesWhenNowBeforeStart()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->blueprint($this->blueprint->handle())
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
            ->blueprint($this->blueprint->handle())
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

        $this->assertEquals($expectedStartDates[0], $occurrences[0]->augmentedValue('start'));
        $this->assertEquals($expectedStartDates[1], $occurrences[1]->augmentedValue('start'));
        $this->assertEquals($expectedStartDates[2], $occurrences[2]->augmentedValue('start'));
        $this->assertEquals($expectedStartDates[3], $occurrences[3]->augmentedValue('start'));
    }

    /** @test */
    public function canLimitUpcomngOccurrences()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->blueprint($this->blueprint->handle())
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
        $occurrences = Events::fromCollection(handle: 'events')
            ->limit(2)
            ->upcoming(10);

        $this->assertCount(2, $occurrences);
    }

    /** @test */
    public function canLimitOccurrencesBetween()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->blueprint($this->blueprint->handle())
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
        $occurrences = Events::fromCollection(handle: 'events')
            ->limit(2)
            ->between(now(), now()->addDays(9)->endOfDay());

        $this->assertCount(2, $occurrences);
    }

    /** @test */
    public function canFilterEvents()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->blueprint($this->blueprint->handle())
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
            ->blueprint($this->blueprint->handle())
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
                ->blueprint($this->blueprint->handle())
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
            ->blueprint($this->blueprint->handle())
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
            ->blueprint($this->blueprint->handle())
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

    // public function test_empty_collection_when_after_end()
    // {
    //     $events = new Events();

    //     $events->add($this->event);

    //     Carbon::setTestNow(Carbon::parse('2019-11-26'));

    //     $nextDates = $events->upcoming(2);

    //     $this->assertCount(0, $nextDates);
    // }

    // public function test_event_pagination()
    // {
    //     $events = new Events();

    //     $events->add($this->event);
    //     $events->add($this->allDayEvent);

    //     Carbon::setTestNow(Carbon::parse('2019-11-19'));

    //     $nextDates = $this->event->upcomingDates(2, 1);

    //     $this->assertCount(2, $nextDates);

    //     $this->assertEquals(Carbon::parse('2019-11-24 11:00'), $nextDates[0]->start());
    //     $this->assertEquals(Carbon::parse('2019-11-25 11:00'), $nextDates[1]->start());

    //     $nextDates = $events->upcoming(2, 2);

    //     $this->assertCount(2, $nextDates);

    //     $this->assertEquals(
    //         Carbon::parse('2019-11-23 19:00'),
    //         Carbon::parse($nextDates[0]->start_date.' '.$nextDates[0]->start_time)
    //     );

    //     $this->assertEquals(
    //         Carbon::parse('2019-11-24 11:00'),
    //         Carbon::parse($nextDates[1]->start_date.' '.$nextDates[1]->start_time)
    //     );
    // }
}
