<?php

namespace TransformStudios\Events\Tests\Feature;

use Illuminate\Support\Carbon;
use Statamic\Facades\Cascade;
use Statamic\Facades\Entry;
use Statamic\Support\Arr;
use TransformStudios\Events\Tags\Events;
use TransformStudios\Events\Tests\PreventSavingStacheItemsToDisk;
use TransformStudios\Events\Tests\TestCase;

class TagTest extends TestCase
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
    public function canGenerateBetweenOccurrences()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'from' => Carbon::now(),
                'to' => Carbon::now()->addWeek(3),
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
                'to' => Carbon::now()->addWeeks(3),
            ]);

        $occurrences = $this->tag->between();

        $this->assertCount(4, $occurrences);
    }

    /** @test */
    public function canGenerateCalendarOccurrences()
    {
        Carbon::setTestNow('jan 1, 2022 10:00');

        Entry::all()->each->delete();

        Entry::make()
            ->collection('events')
            ->slug('single-event-start-of-month')
            ->data([
                'title' => 'Single Event - Start of Month',
                'start_date' => Carbon::now()->startOfMonth()->toDateString(),
                'start_time' => '13:00',
                'end_time' => '15:00',
            ])->save();

        Entry::make()
            ->collection('events')
            ->slug('recurring-event-start-of-month')
            ->data([
                'title' => 'Recurring Event - Start of Month',
                'start_date' => Carbon::now()->startOfMonth()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'weekly',
                'categories' => ['one'],
            ])->save();

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'month' => now()->englishMonth,
                'year' => now()->year,
            ]);

        $occurrences = $this->tag->calendar();

        $this->assertCount(42, $occurrences);
        $this->assertCount(2, Arr::get($occurrences, '6.dates'));
        $this->assertTrue(Arr::get($occurrences, '7.no_results'));
        $this->assertCount(1, Arr::get($occurrences, '13.dates'));
    }

    /** @test */
    public function canGenerateInOccurrences()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'next' => '3 weeks',
            ]);

        $occurrences = $this->tag->in();

        $this->assertCount(4, $occurrences);
    }

    /** @test */
    public function canGenerateTodayOccurrences()
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

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
            ]);

        $this->assertCount(2, $this->tag->today());

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'ignore_finished' => true,
            ]);

        $this->assertCount(1, $this->tag->today());
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
    public function canGenerateUpcomingLimitedOccurrences()
    {
        Entry::make()
            ->collection('events')
            ->slug('another-recurring-event')
            ->id('another-recurring-event')
            ->data([
                'title' => 'Recurring Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'daily',
            ])->save();

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

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'paginate' => 2,
                'limit' => 10,
            ]);

        Cascade::partialMock()->shouldReceive('get')
            ->with('uri')
            ->andReturn('/events');

        $pagination = $this->tag->upcoming();

        $this->assertArrayHasKey('results', $pagination);
        $this->assertArrayHasKey('paginate', $pagination);
        $this->assertArrayHasKey('total_results', $pagination);

        $this->assertCount(2, $pagination['results']);
        $this->assertEquals(2, $pagination['total_results']);
        $this->assertEquals('/events?page=2', $pagination['paginate']['next_page']);
    }

    /** @test */
    public function canGenerateUpcomingOccurrencesWithTaxonomyTerms()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->collection('events')
            ->slug('single-event')
            ->id('single-event')
            ->data([
                'title' => 'Single Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '17:00',
                'end_time' => '19:00',
            ])->save();

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'from' => Carbon::now()->toDateString(),
                'to' => Carbon::now()->addDay()->toDateString(),
                'taxonomy:categories' => 'one',
            ]);

        $occurrences = $this->tag->between();

        $this->assertCount(1, $occurrences);
    }

    /** @test */
    public function canGenerateUpcomingOccurrencesWithFilter()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->collection('events')
            ->slug('single-event')
            ->id('single-event')
            ->data([
                'title' => 'Single Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '17:00',
                'end_time' => '19:00',
            ])->save();

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'from' => Carbon::now()->toDateString(),
                'to' => Carbon::now()->addDay()->toDateString(),
                'title:contains' => 'Single',
            ]);

        $occurrences = $this->tag->between();

        $this->assertCount(1, $occurrences);
    }

    /** @test */
    public function canGenerateDateEventDownloadLink()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'date' => now(),
                'event' => 'recurring-event',
            ]);

        $url = $this->tag->downloadLink();

        $this->assertEquals('http://localhost/!/events/ics?collection=events&date='.now()->toDateString().'&event=recurring-event', $url);
    }

    /** @test */
    public function canGenerateEventDownloadLink()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'event' => 'recurring-event',
            ]);

        $url = $this->tag->downloadLink();

        $this->assertEquals('http://localhost/!/events/ics?collection=events&event=recurring-event', $url);
    }

    /** @test */
    public function canSortOccurrencesDesc()
    {

        $this->tag
            ->setContext([])
            ->setParameters([
                'collection' => 'events',
                'limit' => 3,
                'sort' => 'desc',
            ]);

        $occurrences = $this->tag->upcoming();

        $this->assertTrue($occurrences[0]->start->isAfter($occurrences[1]->start));
        $this->assertTrue($occurrences[1]->start->isAfter($occurrences[2]->start));
    }
}
