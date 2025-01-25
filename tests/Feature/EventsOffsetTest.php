<?php

namespace TransformStudios\Events\Tests\Feature;

use Illuminate\Support\Carbon;
use Statamic\Facades\Cascade;
use Statamic\Facades\Entry;
use TransformStudios\Events\Tags\Events;
use TransformStudios\Events\Tests\PreventSavingStacheItemsToDisk;
use TransformStudios\Events\Tests\TestCase;

class EventsOffsetTest extends TestCase
{
  use PreventSavingStacheItemsToDisk;

  private Events $tag;

  public function setUp(): void
  {
      parent::setUp();

      Entry::make()
          ->collection('events')
          ->slug('recurring-event')
          ->id('recurring-event')
          ->data([
              'title' => 'Recurring Event',
              'start_date' => Carbon::now()->toDateString(),
              'start_time' => '11:00',
              'end_time' => '12:00',
              'recurrence' => 'weekly',
              'categories' => ['one'],
          ])->save();

      $this->tag = app(Events::class);
  }

  /** @test */
  public function canOffsetUpcomingOccurrences()
  {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'limit' => 5,
            'offset' => 2,
        ]);

    $occurrences = $this->tag->upcoming();

    $this->assertCount(3, $occurrences);
  }

  /** @test */
  public function canOffsetBetweenOccurrences()
  {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag->setContext([])
        ->setParameters([
            'collection' => 'events',
            'from' => Carbon::now()->toDateString(),
            'to' => Carbon::now()->addWeek(3),
            'offset' => 2,
        ]);

    $occurrences = $this->tag->between();

    $this->assertCount(2, $occurrences);
  }

  /** @test */
  public function canOffsetTodayOccurrences()
  {
    Carbon::setTestNow(now()->setTimeFromTimeString('12:01'));

    Entry::make()
        ->collection('events')
        ->slug('single-event')
        ->data([
            'title' => 'Single Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
        ])->save();

    $this->tag->setContext([])
        ->setParameters([
            'collection' => 'events',
            'offset' => 1,
        ]);

    $this->assertCount(1, $this->tag->today());

    $this->tag->setContext([])
        ->setParameters([
            'collection' => 'events',
            'ignore_finished' => true,
            'offset' => 1,
        ]);

    $this->assertCount(0, $this->tag->today());
  }

  /** @test */
  public function canOffsetSingleDayOccurrences()
  {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag->setContext([])
        ->setParameters([
            'collection' => 'events',
            'offset' => 1,
        ]);

    $this->assertCount(0, $this->tag->today());
  }
}
