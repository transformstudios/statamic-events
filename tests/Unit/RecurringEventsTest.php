<?php

namespace TransformStudios\Events\Tests\Unit;

use Carbon\Carbon;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Tests\TestCase;
use TransformStudios\Events\Types\RecurringEvent;

class RecurringEventsTest extends TestCase
{
    /** @test */
    public function canCreateRecurringEvent()
    {
        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
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

    // public function test_get_end_date_null_if_no_end_date()
    // {
    //     $event = [
    //         'start_date' => Carbon::now()->toDateString(),
    //         'start_time' => '11:00',
    //         'recurrence' => 'daily',
    //         'all_day' => true,
    //     ];

    //     $event = EventFactory::createFromArray($event);

    //     $this->assertNull($event->endDate());
    //     $this->assertNull($event->end());
    // }

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
