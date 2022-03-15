<?php

namespace TransformStudios\Events\Tests\Unit;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Tests\TestCase;
use TransformStudios\Events\Types\SingleDayEvent;

class SingleDayEventsTest extends TestCase
{
    /** @test */
    public function canCreateSingleEvent()
    {
        $entry = Entry::make()
            ->blueprint($this->blueprint)
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
            ]);

        $event = EventFactory::createFromEntry($entry);

        $this->assertTrue($event instanceof SingleDayEvent);
        $this->assertFalse($event->isRecurring());
        $this->assertFalse($event->isMultiDay());
        $this->assertTrue($event->hasEndTime());
        $this->assertEquals(now()->setTimeFromTimeString('12:00'), $event->end());
    }

    /** @test */
    public function canCreateSingleAllDayEvent()
    {
        $entry = Entry::make()
            ->blueprint($this->blueprint)
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'all_day' => true,
            ]);

        $event = EventFactory::createFromEntry($entry);

        $this->assertTrue($event instanceof SingleDayEvent);
        $this->assertTrue($event->isAllDay());
    }

    /** @test */
    public function endIsEndOfDayWhenNoEndTime()
    {
        Carbon::setTestNow(now());

        $entry = Entry::make()
            ->blueprint($this->blueprint)
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
            ]);

        $event = EventFactory::createFromEntry($entry);

        $this->assertFalse($event->hasEndTime());
        $this->assertEquals(now()->endOfDay(), $event->end());
    }

    /** @test */
    public function emptyOccurrencesIfNowAfterEndDate()
    {
        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint)
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
            ]);

        $event = EventFactory::createFromEntry($recurringEntry);

        Carbon::setTestNow(now()->addDays(1));
        $nextOccurrences = $event->nextOccurrences();

        $this->assertEmpty($nextOccurrences);
    }

    /** @test */
    public function canGenerateNextDayIfNowIsBefore()
    {
        $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00');

        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->data([
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
            ]);

        $event = EventFactory::createFromEntry($recurringEntry);

        Carbon::setTestNow($startDate->setTimeFromTimeString('10:59:00'));

        $nextOccurrences = $event->nextOccurrences(2);

        $this->assertCount(1, $nextOccurrences);

        $this->assertEquals($startDate, $nextOccurrences->first()->start);
    }

    /** @test */
    public function canGenerateNextOccurrenceIfNowIsDuring()
    {
        $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00');
        $recurringEntry = Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->data([
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
            ]);

        Carbon::setTestNow($startDate->addMinutes(10));

        $event = EventFactory::createFromEntry($recurringEntry);
        $nextOccurrences = $event->nextOccurrences();

        $this->assertEquals($startDate, $nextOccurrences[0]->start);
    }
}
