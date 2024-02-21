<?php

namespace TransformStudios\Events\Tests\Unit;

use Illuminate\Support\Carbon;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Tests\TestCase;
use TransformStudios\Events\Types\MultiDayEvent;
use TransformStudios\Events\Types\RecurringEvent;
use TransformStudios\Events\Types\SingleDayEvent;

class EventFactoryTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider provideEventData
     */
    public function canGetEventTypeClass(string $class, array $data)
    {
        $entry = Entry::make()
            ->collection('events')
            ->data($data);

        $this->assertEquals($class, EventFactory::getTypeClass($entry));
    }

    /**
     * @test
     *
     * @dataProvider provideEventData
     */
    public function canCreateCorrectEventType(string $class, array $data)
    {
        $entry = Entry::make()
            ->collection('events')
            ->data($data);

        $this->assertInstanceOf($class, EventFactory::createFromEntry($entry));
    }

    public static function provideEventData()
    {
        return [
            [
                SingleDayEvent::class,
                [
                    'start_date' => Carbon::now()->toDateString(),
                    'start_time' => '11:00',
                    'end_time' => '12:00',
                    'timezone' => 'America/Vancouver',
                ],
            ],
            [
                SingleDayEvent::class,
                [
                    'multi_day' => true,
                    'start_date' => Carbon::now()->toDateString(),
                    'start_time' => '11:00',
                    'end_time' => '12:00',
                    'timezone' => 'America/Vancouver',
                ],
            ],
            [
                RecurringEvent::class,
                [
                    'start_date' => Carbon::now()->toDateString(),
                    'end_date' => Carbon::now()->addWeek()->toDateString(),
                    'start_time' => '11:00',
                    'end_time' => '12:00',
                    'recurrence' => 'daily',
                    'timezone' => 'America/Vancouver',
                ],
            ],
            [
                MultiDayEvent::class,
                [
                    'recurrence' => 'multi_day',
                    'days' => [
                        [
                            'date' => '2019-11-23',
                            'start_time' => '19:00',
                            'end_time' => '21:00',
                        ],
                        [
                            'date' => '2019-11-24',
                            'start_time' => '11:00',
                            'end_time' => '15:00',
                        ],
                    ],
                    'timezone' => 'America/Vancouver',
                ],
            ],
        ];
    }
}
