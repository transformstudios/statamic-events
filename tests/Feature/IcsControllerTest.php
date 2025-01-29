<?php

namespace TransformStudios\Events\Tests\Feature;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Entry;
use TransformStudios\Events\Tests\TestCase;

class IcsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Entry::make()
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

    #[Test]
    public function can_create_single_day_event_ics_file()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $response = $this->get(route('statamic.events.ics.show', [
            'date' => now()->toDateString(),
            'event' => 'the-id',
        ]))->assertDownload('single-event.ics');

        $this->assertStringContainsString('DTSTART:'.now()->setTimeFromTimeString('11:00')->format('Ymd\THis'), $response->streamedContent());
        $this->assertStringContainsString('LOCATION:The Location', $response->streamedContent());
    }

    #[Test]
    public function can_create_single_day_recurring_event_ics_file()
    {
        Carbon::setTestNow(now()->addDay()->setTimeFromTimeString('10:00'));

        Entry::make()
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
        ]))->assertDownload('recurring-event.ics');

        $this->assertStringContainsString('DTSTART:'.now()->setTimeFromTimeString('11:00')->format('Ymd\THis'), $response->streamedContent());

        $this->get(route('statamic.events.ics.show', [
            'date' => now()->addDay()->toDateString(),
            'event' => 'the-recurring-id',
        ]))->assertStatus(404);
    }

    #[Test]
    public function can_create_single_day_multiday_event_ics_file()
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
        ]))->assertStatus(404);

        $response = $this->get(route('statamic.events.ics.show', [
            'date' => now()->addDay()->toDateString(),
            'event' => 'the-multi-day-event',
        ]))->assertDownload('multi-day-event.ics');

        $this->assertStringContainsString('DTSTART:'.now()->addDay()->setTimeFromTimeString('11:00')->format('Ymd\THis'), $response->streamedContent());
    }

    #[Test]
    public function throws404_error_when_event_does_not_occur_on_date()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $this->get(route('statamic.events.ics.show', [
            'date' => now()->addDay()->toDateString(),
            'event' => 'the-id',
        ]))->assertStatus(404);
    }

    #[Test]
    public function throws404_error_when_event_does_not_exist()
    {
        Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

        $this->get(route('statamic.events.ics.show', [
            'date' => now()->addDay()->toDateString(),
            'event' => 'does-not-exist',
        ]))->assertStatus(404);
    }
}
