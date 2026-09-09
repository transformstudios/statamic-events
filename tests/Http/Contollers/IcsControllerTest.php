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
            'address' => '123 Main St',
            'location' => 'The Location',
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

    $this->assertStringContainsString('DTSTART:'.now()->setTimeFromTimeString('11:00')->format('Ymd\THis'), $response->streamedContent());
    $this->assertStringContainsString('LOCATION:123 Main St', $content);
    $this->assertStringContainsString('DESCRIPTION:The description', $content);
    $this->assertStringContainsString('GEO:40;50', $content);
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

test('can create ics when location is a group without address', function () {
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

    $response = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'the-grouped-location-id',
    ]))->assertDownload('grouped-location-event.ics');

    $content = $response->streamedContent();

    $this->assertStringNotContainsString('LOCATION:', $content);
    $this->assertStringContainsString('DESCRIPTION:The description', $content);
});

test('falls back to string location when address is missing', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('location-fallback-event')
        ->id('the-location-fallback-id')
        ->data([
            'title' => 'Location Fallback Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'location' => 'The Location',
            'description' => 'The description',
        ])->save();

    $response = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'the-location-fallback-id',
    ]))->assertDownload('location-fallback-event.ics');

    $this->assertStringContainsString('LOCATION:The Location', $response->streamedContent());
});

test('can create recurring ics when location is a group without address', function () {
    Carbon::setTestNow(now()->addDay()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('grouped-recurring-event')
        ->id('the-grouped-recurring-id')
        ->data([
            'title' => 'Grouped Recurring Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'weekly',
            'location' => [
                'details' => 'Virtual',
            ],
            'description' => 'The description',
        ])->save();

    $response = $this->get(route('statamic.events.ics.show', [
        'event' => 'the-grouped-recurring-id',
    ]))->assertDownload('grouped-recurring-event.ics');

    $this->assertStringNotContainsString('LOCATION:', $response->streamedContent());
});

test('can create multi-day ics when location is a group without address', function () {
    Carbon::setTestNow(now());

    Entry::make()
        ->slug('grouped-multi-day-event')
        ->collection('events')
        ->id('the-grouped-multi-day-id')
        ->data([
            'title' => 'Grouped Multi-day Event',
            'multi_day' => true,
            'location' => [
                'details' => 'Virtual',
            ],
            'description' => 'The description',
            'days' => [
                [
                    'date' => now()->toDateString(),
                    'start_time' => '19:00',
                    'end_time' => '21:00',
                ],
            ],
        ])->save();

    $response = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'the-grouped-multi-day-id',
    ]))->assertDownload('grouped-multi-day-event.ics');

    $this->assertStringNotContainsString('LOCATION:', $response->streamedContent());
});

test('multi-day whole-event download includes location url description and geo on every day', function () {
    Carbon::setTestNow(now());

    Entry::make()
        ->slug('multi-day-whole-event')
        ->collection('events')
        ->id('the-multi-day-whole-event')
        ->data([
            'title' => 'Multi-day Whole Event',
            'multi_day' => true,
            'address' => '123 Main St',
            'link' => 'https://example.com/join',
            'coordinates' => [
                'latitude' => 40,
                'longitude' => 50,
            ],
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

    expect(substr_count($content, 'LOCATION:123 Main St'))->toBe(2)
        ->and(substr_count($content, 'URL:https://example.com/join'))->toBe(2)
        ->and(substr_count($content, 'DESCRIPTION:The description'))->toBe(2)
        ->and(substr_count($content, 'GEO:40;50'))->toBe(2);
});

test('partial coordinates do not fatal and omit geo', function () {
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
            'address' => '123 Main St',
            'coordinates' => [
                'latitude' => 40,
            ],
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'partial-coords-id',
    ]))->assertDownload('partial-coords-event.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:123 Main St', $content);
    $this->assertStringNotContainsString('GEO:', $content);
});

test('non-numeric coordinates do not fatal and omit geo', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('bad-coords-event')
        ->id('bad-coords-id')
        ->data([
            'title' => 'Bad Coords Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'address' => '123 Main St',
            'coordinates' => [
                'latitude' => 'north',
                'longitude' => 'west',
            ],
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'bad-coords-id',
    ]))->assertDownload('bad-coords-event.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:123 Main St', $content);
    $this->assertStringNotContainsString('GEO:', $content);
});

test('online_url emits URL', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('online-url-event')
        ->id('online-url-id')
        ->data([
            'title' => 'Online URL Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'online_url' => 'https://zoom.us/j/123',
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'online-url-id',
    ]))->assertDownload('online-url-event.ics')->streamedContent();

    $this->assertStringContainsString('URL:https://zoom.us/j/123', $content);
});

test('online_url wins over link when both are set', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('online-over-link-event')
        ->id('online-over-link-id')
        ->data([
            'title' => 'Online Over Link Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'online_url' => 'https://zoom.us/j/123',
            'link' => 'https://example.com/old-link',
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'online-over-link-id',
    ]))->assertDownload('online-over-link-event.ics')->streamedContent();

    $this->assertStringContainsString('URL:https://zoom.us/j/123', $content);
    $this->assertStringNotContainsString('URL:https://example.com/old-link', $content);
});

test('deprecated link alone still emits URL', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('link-only-event')
        ->id('link-only-id')
        ->data([
            'title' => 'Link Only Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'link' => 'https://example.com/join',
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'link-only-id',
    ]))->assertDownload('link-only-event.ics')->streamedContent();

    $this->assertStringContainsString('URL:https://example.com/join', $content);
});

test('deprecated URL-valued location alone still emits LOCATION and URL', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('url-location-event')
        ->id('url-location-id')
        ->data([
            'title' => 'URL Location Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'location' => 'https://zoom.us/j/456',
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'url-location-id',
    ]))->assertDownload('url-location-event.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:https://zoom.us/j/456', $content);
    $this->assertStringContainsString('URL:https://zoom.us/j/456', $content);
});

test('online_url is included on all four download routes', function () {
    Carbon::setTestNow(now());

    Entry::make()
        ->collection('events')
        ->slug('online-single')
        ->id('online-single-id')
        ->data([
            'title' => 'Online Single',
            'start_date' => now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'online_url' => 'https://zoom.us/j/single',
        ])->save();

    Entry::make()
        ->collection('events')
        ->slug('online-recurring')
        ->id('online-recurring-id')
        ->data([
            'title' => 'Online Recurring',
            'start_date' => now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'weekly',
            'online_url' => 'https://zoom.us/j/recurring',
        ])->save();

    Entry::make()
        ->collection('events')
        ->slug('online-multi-day')
        ->id('online-multi-day-id')
        ->data([
            'title' => 'Online Multi Day',
            'multi_day' => true,
            'online_url' => 'https://zoom.us/j/multiday',
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

    $single = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'online-single-id',
    ]))->assertDownload('online-single.ics')->streamedContent();

    $recurringDate = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'online-recurring-id',
    ]))->assertDownload('online-recurring.ics')->streamedContent();

    $recurringWhole = $this->get(route('statamic.events.ics.show', [
        'event' => 'online-recurring-id',
    ]))->assertDownload('online-recurring.ics')->streamedContent();

    $multiDate = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'online-multi-day-id',
    ]))->assertDownload('online-multi-day.ics')->streamedContent();

    $multiWhole = $this->get(route('statamic.events.ics.show', [
        'event' => 'online-multi-day-id',
    ]))->assertDownload('online-multi-day.ics')->streamedContent();

    $this->assertStringContainsString('URL:https://zoom.us/j/single', $single);
    $this->assertStringContainsString('URL:https://zoom.us/j/recurring', $recurringDate);
    $this->assertStringContainsString('URL:https://zoom.us/j/recurring', $recurringWhole);
    $this->assertStringContainsString('URL:https://zoom.us/j/multiday', $multiDate);
    expect(substr_count($multiWhole, 'URL:https://zoom.us/j/multiday'))->toBe(2);
});

test('declared coordinates emit GEO', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('coords-event')
        ->id('coords-id')
        ->data([
            'title' => 'Coords Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'address' => '123 Main St',
            'coordinates' => [
                'latitude' => 49.28,
                'longitude' => -123.12,
            ],
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'coords-id',
    ]))->assertDownload('coords-event.ics')->streamedContent();

    $this->assertStringContainsString('GEO:49.28;-123.12', $content);
});

test('coordinates field rejects non-numeric latitude or longitude', function () {
    $fields = \Statamic\Facades\Fieldset::find('events::event')->fields();

    expect(fn () => $fields->addValues([
        'coordinates' => [
            'latitude' => 'north',
            'longitude' => 50,
        ],
    ])->validator()->validate())->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('coordinates are included on all four download routes', function () {
    Carbon::setTestNow(now());

    Entry::make()
        ->collection('events')
        ->slug('coords-single')
        ->id('coords-single-id')
        ->data([
            'title' => 'Coords Single',
            'start_date' => now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'coordinates' => [
                'latitude' => 40,
                'longitude' => 50,
            ],
        ])->save();

    Entry::make()
        ->collection('events')
        ->slug('coords-recurring')
        ->id('coords-recurring-id')
        ->data([
            'title' => 'Coords Recurring',
            'start_date' => now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'weekly',
            'coordinates' => [
                'latitude' => 40,
                'longitude' => 50,
            ],
        ])->save();

    Entry::make()
        ->collection('events')
        ->slug('coords-multi-day')
        ->id('coords-multi-day-id')
        ->data([
            'title' => 'Coords Multi Day',
            'multi_day' => true,
            'coordinates' => [
                'latitude' => 40,
                'longitude' => 50,
            ],
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

    $single = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'coords-single-id',
    ]))->assertDownload('coords-single.ics')->streamedContent();

    $recurringDate = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'coords-recurring-id',
    ]))->assertDownload('coords-recurring.ics')->streamedContent();

    $recurringWhole = $this->get(route('statamic.events.ics.show', [
        'event' => 'coords-recurring-id',
    ]))->assertDownload('coords-recurring.ics')->streamedContent();

    $multiDate = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'coords-multi-day-id',
    ]))->assertDownload('coords-multi-day.ics')->streamedContent();

    $multiWhole = $this->get(route('statamic.events.ics.show', [
        'event' => 'coords-multi-day-id',
    ]))->assertDownload('coords-multi-day.ics')->streamedContent();

    $this->assertStringContainsString('GEO:40;50', $single);
    $this->assertStringContainsString('GEO:40;50', $recurringDate);
    $this->assertStringContainsString('GEO:40;50', $recurringWhole);
    $this->assertStringContainsString('GEO:40;50', $multiDate);
    expect(substr_count($multiWhole, 'GEO:40;50'))->toBe(2);
});
