<?php

uses(\TransformStudios\Events\Tests\TestCase::class);
use Illuminate\Support\Carbon;
use Statamic\Facades\Entry;

uses(\TransformStudios\Events\Tests\PreventSavingStacheItemsToDisk::class);

beforeEach(function () {
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
});

test('can create single day event ics file', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $response = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'the-id',
    ]))->assertDownload('single-event.ics');

    $this->assertStringContainsString('DTSTART:'.now()->setTimeFromTimeString('11:00')->format('Ymd\THis\Z'), $response->streamedContent());
    $this->assertStringContainsString('LOCATION:The Location', $response->streamedContent());
});

test('can create single day recurring event ics file', function () {
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

    $this->assertStringContainsString('DTSTART:'.now()->setTimeFromTimeString('11:00')->format('Ymd\THis\Z'), $response->streamedContent());

    $this->get(route('statamic.events.ics.show', [
        'date' => now()->addDay()->toDateString(),
        'event' => 'the-recurring-id',
    ]))->assertStatus(404);
});

test('can create single day multiday event ics file', function () {
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

    $this->assertStringContainsString('DTSTART:'.now()->addDay()->setTimeFromTimeString('11:00')->format('Ymd\THis\Z'), $response->streamedContent());
});

test('throws404 error when event does not occur on date', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->get(route('statamic.events.ics.show', [
        'date' => now()->addDay()->toDateString(),
        'event' => 'the-id',
    ]))->assertStatus(404);
});

test('throws404 error when event does not exist', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->get(route('statamic.events.ics.show', [
        'date' => now()->addDay()->toDateString(),
        'event' => 'does-not-exist',
    ]))->assertStatus(404);
});