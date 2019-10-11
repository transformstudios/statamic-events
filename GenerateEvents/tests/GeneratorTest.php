<?php

use Carbon\Carbon;
use Statamic\Testing\TestCase;
use Statamic\Addons\GenerateEvents\Generator;

class GeneratorTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_can_generate_next_day_if_after()
    {
        $date = Carbon::now();

        $generator = new Generator(clone $date, 'daily');

        $nextOccurrence = $generator->nextOccurrence((clone $date)->addHour());

        $this->assertTrue($date->addDay()->isSameDay($nextOccurrence));
    }

    public function test_can_generate_next_day_if_before()
    {
        $date = Carbon::now();

        $generator = new Generator(clone $date, 'daily');

        $nextOccurrence = $generator->nextOccurrence((clone $date)->subDays(5));

        $this->assertTrue($date->isSameDay($nextOccurrence));
    }

    public function test_can_generate_next_weekly_date_when_after()
    {
        $startDate = Carbon::now();

        $generator = new Generator(clone $startDate, 'weekly');

        $laterDay = $generator->nextOccurrence((clone $startDate)->addDays(3));

        $this->assertTrue((clone $startDate)->addWeek()->isSameDay($laterDay));
    }

    public function test_can_generate_next_weekly_date_when_a_week_after()
    {
        $startDate = Carbon::now()->second(0);

        $generator = new Generator(clone $startDate, 'weekly');

        $sameDay = $generator->nextOccurrence((clone $startDate)->addDays(7));

        $this->assertTrue((clone $startDate)->addWeeks(2)->isSameDay($sameDay));
    }

    public function test_can_generate_next_weekly_date_when_before()
    {
        $startDate = Carbon::now()->second(0);

        $generator = new Generator(clone $startDate, 'weekly');

        $earlierDay = $generator->nextOccurrence((clone $startDate)->subDays(3));
        $this->assertTrue($startDate->isSameDay($earlierDay));

        $earlierDay = $generator->nextOccurrence((clone $startDate)->subWeeks(1)->subDays(3));
        $this->assertTrue($startDate->isSameDay($earlierDay));
    }

    public function test_can_generate_next_monthly_date_if_after()
    {
        $startDate = Carbon::now()->second(0);

        $generator = new Generator(clone $startDate, 'monthly');

        $nextOccurrence = $generator->nextOccurrence((clone $startDate)->addDays(5));
        $this->assertTrue((clone $startDate)->addMonths(1)->isSameDay($nextOccurrence));

        $nextOccurrence = $generator->nextOccurrence((clone $startDate)->addMonths(1)->subDays(5));
        $this->assertTrue((clone $startDate)->addMonths(1)->isSameDay($nextOccurrence));

        $nextOccurrence = $generator->nextOccurrence((
            clone $startDate
        )->addYears(1)->addMonths(1)->addDays(5));
        $this->assertTrue((clone $startDate)->addYears(1)->addMonths(2)->isSameDay($nextOccurrence));
    }

    public function test_can_generate_next_monthly_date_if_before()
    {
        $startDate = Carbon::now()->second(0);

        $generator = new Generator(clone $startDate, 'monthly');

        $nextOccurrence = $generator->nextOccurrence((clone $startDate)->subDays(5));
        $this->assertTrue((clone $startDate)->isSameDay($nextOccurrence));

        $nextOccurrence = $generator->nextOccurrence((clone $startDate)->subMonths(2)->subDay(5));
        $this->assertTrue((clone $startDate)->isSameDay($nextOccurrence));
    }

    // public function test_can_generate_next_x_weeks_if_before()
    // {
    //     $startDate = Carbon::now();

    //     $interval = 3;

    //     $generator = new Generator(clone $startDate, 'every_x_weeks', $interval);

    //     $nextOccurrence = $generator->nextOccurrence((clone $startDate)->subWeeks(4));
    //     $this->assertTrue((clone $startDate)->isSameDay($nextOccurrence));

    //     $nextOccurrence = $generator->nextOccurrence((clone $startDate)->subDays(1));
    //     $this->assertTrue((clone $startDate)->isSameDay($nextOccurrence));
    // }

    // public function test_can_generate_next_x_weeks_if_after()
    // {
    //     $startDate = Carbon::now();

    //     $interval = 3;

    //     $generator = new Generator(clone $startDate, 'every_x_weeks', $interval);

    //     $nextOccurrence = $generator->nextOccurrence((clone $startDate)->addWeeks(4));
    //     $this->assertTrue((clone $startDate)->addWeeks($interval * 2)->isSameDay($nextOccurrence));

    //     $nextOccurrence = $generator->nextOccurrence((clone $startDate)->addWeeks(2));
    //     $this->assertTrue((clone $startDate)->addWeeks($interval)->isSameDay($nextOccurrence));
    // }

    public function test_can_generate_next_x_occurrences_from_before_start_date()
    {
        $startDate = Carbon::now()->second(0);

        for ($x = 0;$x < 3;$x++) {
            $dates[] = (clone $startDate)->addDays($x);
        }

        $generator = new Generator(clone $startDate, 'daily');
        $nextOccurrences = $generator->nextOccurrences(3, (clone $startDate)->subDays(1));

        $this->assertEquals($dates, $nextOccurrences);
    }

    public function test_generates_all_occurences_when_daily_after_start_date()
    {
        $startDate = Carbon::now()->second(0);

        for ($x = 2;$x < 4;$x++) {
            $dates[] = (clone $startDate)->addDays($x);
        }

        $generator = new Generator((clone $startDate)->addDay(), 'daily', (clone $startDate)->addDays(3));

        $nextOccurrences = $generator->nextOccurrences(3, (clone $startDate)->addDays(1));

        $this->assertCount(2, $nextOccurrences);

        $this->assertEquals($dates, $nextOccurrences);
    }

    public function test_generates_all_occurences_when_weekly_after_start_date()
    {
        $startDate = Carbon::now()->second(0);

        for ($x = 1;$x <= 3;$x++) {
            $dates[] = (clone $startDate)->addDay()->addWeeks($x);
        }

        $generator = new Generator((clone $startDate)->addDay(), 'weekly', (clone $startDate)->addDay()->addWeeks(3));

        $nextOccurrences = $generator->nextOccurrences(4, (clone $startDate)->addDays(2));

        $this->assertCount(3, $nextOccurrences);

        $this->assertEquals($dates, $nextOccurrences);
    }
}
