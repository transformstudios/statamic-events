<?php

namespace TransformStudios\Events\Tests\Feature;

use Illuminate\Support\Carbon;
use Statamic\Facades\Entry;
use TransformStudios\Events\Tests\PreventSavingStacheItemsToDisk;
use TransformStudios\Events\Tests\TestCase;

class IcsControllerTest extends TestCase
{
    use PreventSavingStacheItemsToDisk;

    public function setUp(): void
    {
        parent::setUp();

        Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->slug('single-event')
            ->id('the-id')
            ->data([
                'title' => 'Single Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'location' => 'The Location',
            ])->save();
    }

    /** @test */
    public function canCreateSingleDayEventIcsFile()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $response = $this->get(route('statamic.events.ics.show', [
            'date' => now()->toDateString(),
            'event' => 'the-id',
        ]));

        $response->assertDownload('single-event.ics');

        $this->assertStringContainsString('DTSTART:'.now()->setTimeFromTimeString('11:00')->format('Ymd\THis\Z'), $response->streamedContent());
        $this->assertStringContainsString('LOCATION:The Location', $response->streamedContent());
    }

    /** @test */
    public function canCreateSingleDayRecurringEventIcsFile()
    {
        Carbon::setTestNow(now()->addDay()->setTimeFromTimeString('10:00'));

        Entry::make()
            ->blueprint($this->blueprint->handle())
            ->collection('events')
            ->slug('recurring-event')
            ->id('the-recurring-id')
            ->data([
                'title' => 'Recurring Event',
                'start_date' => Carbon::now()->toDateString(),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'recurrence' => 'weekly',
            ])->save();

        $response = $this->get(route('statamic.events.ics.show', [
            'date' => now()->toDateString(),
            'event' => 'the-recurring-id',
        ]));
        $response->assertDownload('recurring-event.ics');

        $this->assertStringContainsString('DTSTART:'.now()->setTimeFromTimeString('11:00')->format('Ymd\THis\Z'), $response->streamedContent());

        $this->get(route('statamic.events.ics.show', [
            'date' => now()->addDay()->toDateString(),
            'event' => 'the-recurring-id',
        ]))->assertSessionHasErrors('event_date');
    }

    /** @test */
    public function canCreateSingleDayMultidayEventIcsFile()
    {
        Carbon::setTestNow(now());

        $entry = Entry::make()
            ->slug('multi-day-event')
            ->collection('events')
            ->id('the-multi-day-event')
            ->data([
                'title' => 'Multi-day Event',
                'multi_day' => true,
                'days' => [
                    [
                        'date' => now()->toDateString(),
                        'start_time' => '19:00',
                        'end_time' => '21:00',
                    ],
                    [
                        'date' => now()->addDay()->toDateString(),
                        'start_time' => '11:00',
                        'end_time' => '15:00',
                    ],
                    [
                        'date' => now()->addDays(2)->toDateString(),
                        'start_time' => '11:00',
                        'end_time' => '15:00',
                    ],
                ],
            ])->save();

        $this->get(route('statamic.events.ics.show', [
            'date' => now()->addDays(3)->toDateString(),
            'event' => 'the-multi-day-event',
        ]))->assertSessionHasErrors('event_date');

        $response = $this->get(route('statamic.events.ics.show', [
            'date' => now()->addDay()->toDateString(),
            'event' => 'the-multi-day-event',
        ]));

        $response->assertDownload('multi-day-event.ics');

        $this->assertStringContainsString('DTSTART:'.now()->addDay()->setTimeFromTimeString('11:00')->format('Ymd\THis\Z'), $response->streamedContent());
    }

    /** @test */
    public function throwsValidationErrorWhenEventDoesNotOccurOnDate()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $response = $this->get(route('statamic.events.ics.show', [
            'date' => now()->addDay()->toDateString(),
            'event' => 'the-id',
        ]))->assertSessionHasErrors('event_date');
    }
    /** @test */
    public function throwsValidationErrorWhenEventDoesNotExist()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $response = $this->get(route('statamic.events.ics.show', [
            'date' => now()->addDay()->toDateString(),
            'event' => 'does-not-exist',
        ]))->assertSessionHasErrors('event_date');
    }
}
