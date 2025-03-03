<?php

namespace TransformStudios\Events\Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Tests\TestCase;
use TransformStudios\Events\Types\MultiDayEvent;

class MultiDayEventsTest extends TestCase
{
    /** @var MultiDayEvent */
    private $allDayEvent;

    /** @var MultiDayEvent */
    private $event;

    /** @var MultiDayEvent */
    private $noEndTimeEvnt;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNowAndTimezone(now(), 'America/Vancouver');

        $entry = Entry::make()
            ->slug('multi-day-event')
            ->collection('events')
            ->data([
                'recurrence' => 'multi_day',
                'days' => [
                    [
                        'date' => '2019-11-24',
                        'start_time' => '11:00',
                        'end_time' => '15:00',
                    ],
                    [
                        'date' => '2019-11-25',
                        'start_time' => '11:00',
                        'end_time' => '15:00',
                    ],
                    [
                        'date' => '2019-11-23',
                        'start_time' => '19:00',
                        'end_time' => '21:00',
                    ],
                ],
                'timezone' => 'America/Vancouver',
            ]);

        $this->event = EventFactory::createFromEntry($entry);

        $noEndTimeEntry = Entry::make()
            ->collection('events')
            ->slug('no-end-time')
            ->data([
                'recurrence' => 'multi_day',
                'days' => [
                    [
                        'date' => '2019-11-23',
                        'start_time' => '19:00',
                    ],
                    [
                        'date' => '2019-11-24',
                        'start_time' => '15:00',
                    ],
                ],
                'timezone' => 'America/Vancouver',
            ]);

        $this->noEndTimeEvent = EventFactory::createFromEntry($noEndTimeEntry);

        $allDayEntry = Entry::make()
            ->collection('events')
            ->data([
                'recurrence' => 'multi_day',
                'days' => [
                    [
                        'date' => '2019-11-20',
                    ],
                    [
                        'date' => '2019-11-21',
                    ],
                ],
                'timezone' => 'America/Vancouver',
            ]);
        $this->allDayEvent = EventFactory::createFromEntry($allDayEntry);
    }

    #[Test]
    public function canCreateMultiDayEvent()
    {
        $this->assertTrue($this->event instanceof MultiDayEvent);
        $this->assertTrue($this->allDayEvent instanceof MultiDayEvent);
        $this->assertTrue($this->noEndTimeEvent instanceof MultiDayEvent);
        $this->assertTrue($this->event->isMultiDay());
        $this->assertTrue($this->allDayEvent->isMultiDay());
        $this->assertTrue($this->noEndTimeEvent->isMultiDay());
    }

    #[Test]
    public function canGetStart()
    {
        $this->assertEquals(
            Carbon::parse('2019-11-23 19:00')->shiftTimezone('America/Vancouver'),
            $this->event->start()
        );
        $this->assertEquals(
            Carbon::parse('2019-11-20 0:00')->shiftTimezone('America/Vancouver'),
            $this->allDayEvent->start()
        );
        $this->assertEquals(
            Carbon::parse('2019-11-20 0:00')->shiftTimezone('America/Vancouver')->timezone,
            $this->event->start()->timezone
        );
    }

    #[Test]
    public function canGetEnd()
    {
        $this->assertEquals(
            Carbon::parse('2019-11-25 15:00')->shiftTimezone('America/Vancouver'),
            $this->event->end()
        );
        $this->assertEquals(
            Carbon::parse('2019-11-21 23:59:59.999999')->shiftTimezone('America/Vancouver'),
            $this->allDayEvent->end()
        );
        $this->assertEquals(
            Carbon::parse('2019-11-21 23:59:00')->shiftTimezone('America/Vancouver')->timezone,
            $this->event->end()->timezone
        );
    }

    #[Test]
    public function noOccurrencesIfNowAfterEndDate()
    {
        Carbon::setTestNow('2019-11-26');
        $this->assertEmpty($this->event->nextOccurrences(1));
    }

    #[Test]
    public function canGenerateNextOccurrenceIfBefore()
    {
        Carbon::setTestNowAndTimezone('2019-11-22', 'America/Vancouver');

        $this->assertEquals(
            Carbon::parse('2019-11-23')->setTimeFromTimeString('19:00:00'),
            $this->event->nextOccurrences()[0]->start
        );
        $this->assertEquals(
            Carbon::parse('2019-11-23')->setTimeFromTimeString('21:00'),
            $this->event->nextOccurrences()[0]->end
        );
    }

    #[Test]
    public function canGenerateNextOccurrenceIfDuring()
    {
        Carbon::setTestNowAndTimezone('2019-11-24 10:00', 'America/Vancouver');
        $this->assertEquals(
            Carbon::parse('2019-11-24')->setTimeFromTimeString('11:00:00'),
            $this->event->nextOccurrences()[0]->start
        );
    }

    #[Test]
    public function dayIsAllDayWhenNoStartAndEndTime()
    {
        $days = $this->allDayEvent->days();

        $this->assertTrue($days[0]->isAllDay());
    }
}
