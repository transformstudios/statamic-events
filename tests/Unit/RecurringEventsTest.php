<?php

namespace TransformStudios\Events\Tests\Unit;

use Carbon\Carbon;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events;
use TransformStudios\Events\Tests\TestCase;
use TransformStudios\Events\Types\MultiDayEvent;
use TransformStudios\Events\Types\RecurringEvent;

class RecurringEventsTest extends TestCase
{
    /** @test */
    public function canCreateRecurringEvent()
    {
        $recurringEntry = Entry::make()
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'recurrence' => 'daily',
            ]);

        $event = EventFactory::createFromEntry($recurringEntry);

        $this->assertTrue($event instanceof RecurringEvent);
        $this->assertTrue($event->isRecurring());
        $this->assertFalse($event->isMultiDay());
    }

    /** @test */
    public function wontCreateRecurringEventWhenMultiDay()
    {
        $recurringEntry = Entry::make()
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'recurrence' => 'multi_day',
            ]);

        $event = EventFactory::createFromEntry($recurringEntry);

        $this->assertTrue($event instanceof MultiDayEvent);
        $this->assertFalse($event->isRecurring());
        $this->assertTrue($event->isMultiDay());
    }

    /** @test */
    public function canShowLastOccurrenceWhenNoEndTime()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $recurringEntry = tap(Entry::make()
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->addDays(1)->toDateString(),
                'start_time' => '22:00',
                'recurrence' => 'daily',
                'end_date' => Carbon::now()->addDays(2)->toDateString(),
                'timezone' => 'America/Chicago',
            ]))->save();

        $occurrences = Events::fromCollection(handle: 'events')
            ->between(Carbon::now(), Carbon::now()->addDays(5)->endOfDay());

        $this->assertCount(2, $occurrences);
    }
}
