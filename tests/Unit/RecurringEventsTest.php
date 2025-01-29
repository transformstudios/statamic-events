<?php

namespace TransformStudios\Events\Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events;
use TransformStudios\Events\Tests\TestCase;
use TransformStudios\Events\Types\RecurringEvent;
use TransformStudios\Events\Types\SingleDayEvent;

class RecurringEventsTest extends TestCase
{
    #[Test]
    public function can_create_recurring_event()
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

    #[Test]
    public function wont_create_recurring_event_when_multi_day()
    {
        $recurringEntry = Entry::make()
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'recurrence' => 'multi_day',
            ]);

        $event = EventFactory::createFromEntry($recurringEntry);

        $this->assertTrue($event instanceof SingleDayEvent);
        $this->assertFalse($event->isRecurring());
        $this->assertFalse($event->isMultiDay());
    }

    #[Test]
    public function can_show_last_occurrence_when_no_end_time()
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

    #[Test]
    public function can_generate_monthly_by_day_occurrences()
    {
        Carbon::setTestNow(Carbon::parse('Jan 29 2025 10:00am'));

        $recurringEntry = tap(Entry::make()
            ->collection('events')
            ->data([
                'start_date' => ray()->pass(Carbon::now()->addDays(1)->toDateString()),
                'start_time' => '22:00',
                'recurrence' => 'monthly',
                'end_date' => ray()->pass(Carbon::now()->addMonths(3)->toDateString()),
                'timezone' => 'America/Chicago',
                'specific_days' => ['first_sunday', 'last_wednesday'],
            ]))->save();

        $occurrences = Events::fromCollection(handle: 'events')
            ->between(Carbon::now(), Carbon::now()->addMonths(4));

        $this->assertCount(5, $occurrences);
    }
}
