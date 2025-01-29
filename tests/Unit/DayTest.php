<?php

namespace TransformStudios\Events\Tests\Unit;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use TransformStudios\Events\Day;
use TransformStudios\Events\Tests\TestCase;

class DayTest extends TestCase
{
    #[Test]
    public function can_get_end_when_no_end_time()
    {
        $dayData = [
            'date' => '2019-11-23',
            'start_time' => '19:00',
        ];

        $day = new Day(data: $dayData, timezone: 'America/Vancouver');

        $this->assertEquals(
            Carbon::parse('2019-11-23')->shiftTimezone('America/Vancouver')->endOfDay(),
            $day->end()
        );
    }

    #[Test]
    public function has_no_end_time_when_no_end_time()
    {
        $dayData = [
            'date' => '2019-11-23',
            'start_time' => '19:00',
        ];

        $day = new Day(data: $dayData, timezone: 'America/Vancouver');

        $this->assertFalse($day->hasEndTime());
    }
}
