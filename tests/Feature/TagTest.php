<?php

namespace TransformStudios\Events\Tests\Feature;

use Illuminate\Support\Carbon;
use Statamic\Facades\Entry;
use TransformStudios\Events\Tags\Events;
use TransformStudios\Events\Tests\PreventSavingStacheItemsToDisk;
use TransformStudios\Events\Tests\TestCase;
use TransformStudios\Events\Types\RecurringEvent;

class TagTest extends TestCase
{
    use PreventSavingStacheItemsToDisk;

    private Events $tag;

    public function setUp(): void
    {
        parent::setUp();

        Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->slug('recurring-event')
            ->data([
                'title' => 'Recurring Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
                'categories' => ['one'],
            ])->save();

        $this->tag = app(Events::class);
    }

    /** @test */
    public function canGenerateBetweenOccurrences()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'from' => Carbon::now(),
                'to' => Carbon::now()->addDay(3),
            ]);

        $occurrences = $this->tag->between();

        $this->assertCount(4, $occurrences);
    }

    /** @test */
    public function canGenerateBetweenOccurrencesWithDefaultFrom()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'to' => Carbon::now()->addDay(3),
            ]);

        $occurrences = $this->tag->between();

        $this->assertCount(4, $occurrences);
    }

        /** @test */
        public function canGenerateInOccurrences()
        {
            Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

            $this->tag
                ->setContext([])
                ->setParameters([
                    'collection' => 'events',
                    'next' => '3 days',
                ]);

            $occurrences = $this->tag->in();

            $this->assertCount(4, $occurrences);
        }

    /** @test */
    public function canGenerateUpcomingOccurrences()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'limit' => 3,
            ]);

        $occurrences = $this->tag->upcoming();

        $this->assertCount(3, $occurrences);
    }

    /** @test */
    public function canPaginateUpcomingOccurrences()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->slug('recurring-event')
            ->data([
                'title' => 'Recurring Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
                'categories' => ['one'],
            ])->save();

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'paginate' => true,
                'per_page' => 2,
                'limit' => 10,
            ]);

        $pagination = $this->tag->upcoming();

        $this->assertArrayHasKey('results', $pagination);
        $this->assertArrayHasKey('paginate', $pagination);
        $this->assertArrayHasKey('total_results', $pagination);

        $this->assertCount(2, $pagination['results']);
        $this->assertEquals(2, $pagination['total_results']);
    }
}
