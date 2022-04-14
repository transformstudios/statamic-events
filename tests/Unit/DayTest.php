<?php

namespace TransformStudios\Events\Tests\Unit;

use Illuminate\Support\Carbon;
use TransformStudios\Events\Day;
use TransformStudios\Events\Tests\TestCase;

class DayTest extends TestCase
{
    /** @test */
    public function canGetEndWhenNoEndTime()
    {
        $dayData = [
            'date' => '2019-11-23',
            'start_time' => '19:00',
        ];

        $day = new Day(data: $dayData);

        $this->assertEquals(Carbon::parse('2019-11-23')->endOfDay()->setMicrosecond(0), $day->end());
    }
    /** @test */
    public function hasNoEndTimeWhenNoEndTime()
    {
        $dayData = [
            'date' => '2019-11-23',
            'start_time' => '19:00',
        ];

        $day = new Day(data: $dayData);

        $this->assertFalse($day->hasEndTime());
    }
}
