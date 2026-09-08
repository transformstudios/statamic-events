<?php

namespace TransformStudios\Events\Tests\Http\Controllers;

use Illuminate\Support\Carbon;
use Statamic\Facades\Entry;

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
            'location' => '123 Main St',
            'coordinates' => [
                'latitude' => 40,
                'longitude' => 50,
            ],
            'description' => 'The description',
        ])->save();
});

test('can create single day event ics file', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $response = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'the-id',
    ]))->assertDownload('single-event.ics');

    $content = $response->streamedContent();

    $this->assertStringContainsString('DTSTART:'.now()->setTimeFromTimeString('11:00')->format('Ymd\THis'), $content);
    $this->assertStringContainsString('LOCATION:123 Main St', $content);
    $this->assertStringContainsString('DESCRIPTION:The description', $content);
    $this->assertStringContainsString('GEO:40;50', $content);
    $this->assertStringNotContainsString('URL:', $content);
});

test('physical only maps location without url', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('physical-event')
        ->id('physical-id')
        ->data([
            'title' => 'Physical Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'location' => 'Outside the side exit',
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'physical-id',
    ]))->assertDownload('physical-event.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:Outside the side exit', $content);
    $this->assertStringNotContainsString('URL:', $content);
});

test('online only maps online_url to location and url', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('online-event')
        ->id('online-id')
        ->data([
            'title' => 'Online Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'online_url' => 'https://zoom.us/j/123',
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'online-id',
    ]))->assertDownload('online-event.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:https://zoom.us/j/123', $content);
    $this->assertStringContainsString('URL:https://zoom.us/j/123', $content);
});

test('hybrid maps location and online_url separately', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('hybrid-event')
        ->id('hybrid-id')
        ->data([
            'title' => 'Hybrid Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'location' => '123 Main St, Surrey, BC',
            'online_url' => 'https://zoom.us/j/456',
            'coordinates' => [
                'latitude' => 40,
                'longitude' => 50,
            ],
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'hybrid-id',
    ]))->assertDownload('hybrid-event.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:123 Main St\\, Surrey\\, BC', $content);
    $this->assertStringContainsString('URL:https://zoom.us/j/456', $content);
    $this->assertStringContainsString('GEO:40;50', $content);
});

test('partial coordinates are not fatal and omit geo', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('partial-coords-event')
        ->id('partial-coords-id')
        ->data([
            'title' => 'Partial Coords Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'location' => 'The Hall',
            'coordinates' => [
                'latitude' => 40,
            ],
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'partial-coords-id',
    ]))->assertDownload('partial-coords-event.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:The Hall', $content);
    $this->assertStringNotContainsString('GEO:', $content);
});

test('non-string location is ignored rather than fatal', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('grouped-location-event')
        ->id('the-grouped-location-id')
        ->data([
            'title' => 'Grouped Location Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'location' => [
                'details' => 'Virtual',
                'coordinates' => [
                    'latitude' => 40,
                    'longitude' => 50,
                ],
            ],
            'description' => 'The description',
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'the-grouped-location-id',
    ]))->assertDownload('grouped-location-event.ics')->streamedContent();

    $this->assertStringNotContainsString('LOCATION:', $content);
    $this->assertStringContainsString('DESCRIPTION:The description', $content);
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
            'location' => 'The Location',
            'description' => 'The description',
        ])->save();

    $response = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'the-recurring-id',
    ]))->assertDownload('recurring-event.ics');

    $this->assertStringContainsString('DTSTART:'.now()->setTimeFromTimeString('11:00')->format('Ymd\THis'), $response->streamedContent());
    $this->assertStringContainsString('LOCATION:The Location', $response->streamedContent());
    $this->assertStringContainsString('DESCRIPTION:The description', $response->streamedContent());

    $this->get(route('statamic.events.ics.show', [
        'date' => now()->addDay()->toDateString(),
        'event' => 'the-recurring-id',
    ]))->assertStatus(404);
});

test('can create ics with single date recurrence', function () {
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
            'location' => 'The Location',
            'description' => 'The description',
        ])->save();

    $response = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'the-recurring-id',
    ]))->assertDownload('recurring-event.ics');

    $this->assertStringContainsString('DTSTART:'.now()->setTimeFromTimeString('11:00')->format('Ymd\THis'), $response->streamedContent());
    $this->assertStringContainsString('LOCATION:The Location', $response->streamedContent());
    $this->assertStringContainsString('DESCRIPTION:The description', $response->streamedContent());
});

test('can create ics with recurrence', function () {
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
            'location' => 'The Location',
            'description' => 'The description',
        ])->save();

    $response = $this->get(route('statamic.events.ics.show', [
        'event' => 'the-recurring-id',
    ]))->assertDownload('recurring-event.ics');

    $this->assertStringContainsString('DTSTART:'.now()->setTimeFromTimeString('11:00')->format('Ymd\THis'), $response->streamedContent());
    $this->assertStringContainsString('LOCATION:The Location', $response->streamedContent());
    $this->assertStringContainsString('DESCRIPTION:The description', $response->streamedContent());
});

test('can create single day multiday event ics file', function () {
    Carbon::setTestNow(now());

    Entry::make()
        ->slug('multi-day-event')
        ->collection('events')
        ->id('the-multi-day-event')
        ->data([
            'title' => 'Multi-day Event',
            'multi_day' => true,
            'location' => 'The Location',
            'description' => 'The description',
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
    $this->assertStringContainsString('LOCATION:The Location', $response->streamedContent());
    $this->assertStringContainsString('DESCRIPTION:The description', $response->streamedContent());
});

test('multi-day whole-event download includes location url and description', function () {
    Carbon::setTestNow(now());

    Entry::make()
        ->slug('multi-day-whole-event')
        ->collection('events')
        ->id('the-multi-day-whole-event')
        ->data([
            'title' => 'Multi-day Whole Event',
            'multi_day' => true,
            'location' => 'The Hall',
            'online_url' => 'https://zoom.us/j/789',
            'description' => 'The description',
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
            ],
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'event' => 'the-multi-day-whole-event',
    ]))->assertDownload('multi-day-whole-event.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:The Hall', $content);
    $this->assertStringContainsString('URL:https://zoom.us/j/789', $content);
    $this->assertStringContainsString('DESCRIPTION:The description', $content);
});

test('throws 404 error when event does not occur on date', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->get(route('statamic.events.ics.show', [
        'date' => now()->addDay()->toDateString(),
        'event' => 'the-id',
    ]))->assertStatus(404);
});

test('throws 404 error when event does not exist', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->get(route('statamic.events.ics.show', [
        'date' => now()->addDay()->toDateString(),
        'event' => 'does-not-exist',
    ]))->assertStatus(404);
});

test('throws 404 error when date is invalid', function () {
    $this->get(route('statamic.events.ics.show', [
        'date' => 'not-a-date',
        'event' => 'the-id',
    ]))->assertStatus(404);
});
