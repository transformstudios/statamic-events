<?php

namespace TransformStudios\Events\Tests\Unit;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonTimeZone;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Tests\TestCase;
use TransformStudios\Events\Types\SingleDayEvent;

class SingleDayEventsTest extends TestCase
{
    #[Test]
    public function canCreateSingleEvent()
    {
        $entry = Entry::make()
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'timezone' => 'America/Vancouver',
            ]);

        $event = EventFactory::createFromEntry($entry);

        $this->assertTrue($event instanceof SingleDayEvent);
        $this->assertFalse($event->isRecurring());
        $this->assertFalse($event->isMultiDay());
        $this->assertTrue($event->hasEndTime());
        $this->assertEquals(new CarbonTimeZone('America/Vancouver'), $event->start()->timezone);
        $this->assertEquals(new CarbonTimeZone('America/Vancouver'), $event->end()->timezone);
    }

    #[Test]
    public function canCreateSingleAllDayEvent()
    {
        $entry = Entry::make()
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'all_day' => true,
            ]);

        $event = EventFactory::createFromEntry($entry);

        $this->assertTrue($event instanceof SingleDayEvent);
        $this->assertTrue($event->isAllDay());
    }

    #[Test]
    public function endIsEndOfDayWhenNoEndTime()
    {
        Carbon::setTestNow(now());

        $entry = Entry::make()
            ->collection('events')
            ->data([
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
            ]);

        $event = EventFactory::createFromEntry($entry);

        $this->assertEquals('23:59:59', $event->endTime());
        $nextOccurrences = $event->nextOccurrences();

        $this->assertFalse($nextOccurrences[0]->has_end_time);
        $this->assertEquals(now()->endOfDay()->setMicrosecond(0), $nextOccurrences[0]->end);
    }

    #[Test]
    public function emptyOccurrencesIfNowAfterEndDate()
    {
        $recurringEntry = Entry::make()
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
            ]);

        $event = EventFactory::createFromEntry($recurringEntry);

        Carbon::setTestNow($startDate->setTimeFromTimeString('10:59:00'));

        $nextOccurrences = $event->nextOccurrences(2);

        $this->assertCount(1, $nextOccurrences);

        $this->assertEquals($startDate, $nextOccurrences->first()->start);
    }

    #[Test]
    public function canGenerateNextOccurrenceIfNowIsDuring()
    {
        $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00');
        $single = Entry::make()
            ->collection('events')
            ->data([
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
            ]);

        $singleNoEndTime = Entry::make()
            ->collection('events')
            ->data([
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
            ]);

        Carbon::setTestNow($startDate->addMinutes(10));

        $event = EventFactory::createFromEntry($single);
        $noEndTimeEvent = EventFactory::createFromEntry($singleNoEndTime);
        $nextOccurrences = $event->nextOccurrences();

        $this->assertEquals($startDate, $nextOccurrences[0]->start);
        $this->assertEquals($startDate, $noEndTimeEvent->nextOccurrences()[0]->start);
    }

    #[Test]
    public function canSupplementNoEndTime()
    {
        $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00');
        $noEndTimeEntry = Entry::make()
            ->collection('events')
            ->data([
                'start_date' => $startDate->toDateString(),
                'start_time' => '11:00',
            ]);

        Carbon::setTestNow($startDate->addMinutes(10));

        $event = EventFactory::createFromEntry($noEndTimeEntry);
        $nextOccurrences = $event->nextOccurrences();

        $this->assertFalse($nextOccurrences[0]->has_end_time);
    }
}
