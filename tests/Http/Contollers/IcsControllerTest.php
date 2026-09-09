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
            'location' => [
                'name' => '123 Main St',
                'coordinates' => [
                    'latitude' => 40,
                    'longitude' => 50,
                ],
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
            'location' => [
                'name' => 'The Location',
            ],
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
            'location' => [
                'name' => 'The Location',
            ],
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
            'location' => [
                'name' => 'The Location',
            ],
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
            'location' => [
                'name' => 'The Location',
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

test('location group without name does not emit LOCATION', function () {
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
    $this->assertStringContainsString('GEO:40;50', $content);
    $this->assertStringContainsString('DESCRIPTION:The description', $content);
});

test('string location is ignored', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('string-location-event')
        ->id('string-location-id')
        ->data([
            'title' => 'String Location Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'location' => 'The Location',
            'description' => 'The description',
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'string-location-id',
    ]))->assertDownload('string-location-event.ics')->streamedContent();

    $this->assertStringNotContainsString('LOCATION:', $content);
});

test('address and link and top-level coordinates are no longer read', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('legacy-handles-event')
        ->id('legacy-handles-id')
        ->data([
            'title' => 'Legacy Handles Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'address' => '123 Main St',
            'link' => 'https://example.com/join',
            'coordinates' => [
                'latitude' => 40,
                'longitude' => 50,
            ],
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'legacy-handles-id',
    ]))->assertDownload('legacy-handles-event.ics')->streamedContent();

    $this->assertStringNotContainsString('LOCATION:', $content);
    $this->assertStringNotContainsString('URL:', $content);
    $this->assertStringNotContainsString('GEO:', $content);
});

test('url in location name is treated as a location not a join url', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('url-name-event')
        ->id('url-name-id')
        ->data([
            'title' => 'URL Name Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'location' => [
                'name' => 'https://zoom.us/j/456',
            ],
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'url-name-id',
    ]))->assertDownload('url-name-event.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:https://zoom.us/j/456', $content);
    $this->assertStringNotContainsString('URL:', $content);
});

test('physical only emits LOCATION and GEO', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('physical-only-event')
        ->id('physical-only-id')
        ->data([
            'title' => 'Physical Only Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'location' => [
                'name' => '123 Main St',
                'coordinates' => [
                    'latitude' => 40,
                    'longitude' => 50,
                ],
            ],
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'physical-only-id',
    ]))->assertDownload('physical-only-event.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:123 Main St', $content);
    $this->assertStringContainsString('GEO:40;50', $content);
    $this->assertStringNotContainsString('URL:', $content);
});

test('online only emits LOCATION and URL from online_url', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('online-only-event')
        ->id('online-only-id')
        ->data([
            'title' => 'Online Only Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'online_url' => 'https://zoom.us/j/123',
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'online-only-id',
    ]))->assertDownload('online-only-event.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:https://zoom.us/j/123', $content);
    $this->assertStringContainsString('URL:https://zoom.us/j/123', $content);
    $this->assertStringNotContainsString('GEO:', $content);
});

test('hybrid emits location name URL and GEO', function () {
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
            'location' => [
                'name' => '123 Main St',
                'coordinates' => [
                    'latitude' => 40,
                    'longitude' => 50,
                ],
            ],
            'online_url' => 'https://zoom.us/j/123',
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'hybrid-id',
    ]))->assertDownload('hybrid-event.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:123 Main St', $content);
    $this->assertStringContainsString('URL:https://zoom.us/j/123', $content);
    $this->assertStringContainsString('GEO:40;50', $content);
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
            'location' => [
                'name' => '123 Main St',
                'coordinates' => [
                    'latitude' => 40,
                    'longitude' => 50,
                ],
            ],
            'online_url' => 'https://example.com/join',
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
            'location' => [
                'name' => '123 Main St',
                'coordinates' => [
                    'latitude' => 40,
                ],
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
            'location' => [
                'name' => '123 Main St',
                'coordinates' => [
                    'latitude' => 'north',
                    'longitude' => 'west',
                ],
            ],
        ])->save();

    $content = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'bad-coords-id',
    ]))->assertDownload('bad-coords-event.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:123 Main St', $content);
    $this->assertStringNotContainsString('GEO:', $content);
});

test('location and online_url are included on all four download routes', function () {
    Carbon::setTestNow(now());

    Entry::make()
        ->collection('events')
        ->slug('place-single')
        ->id('place-single-id')
        ->data([
            'title' => 'Place Single',
            'start_date' => now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'location' => [
                'name' => 'Hall A',
                'coordinates' => [
                    'latitude' => 10,
                    'longitude' => 20,
                ],
            ],
            'online_url' => 'https://zoom.us/j/single',
        ])->save();

    Entry::make()
        ->collection('events')
        ->slug('place-recurring')
        ->id('place-recurring-id')
        ->data([
            'title' => 'Place Recurring',
            'start_date' => now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'weekly',
            'location' => [
                'name' => 'Hall B',
            ],
            'online_url' => 'https://zoom.us/j/recurring',
        ])->save();

    Entry::make()
        ->collection('events')
        ->slug('place-multi-day')
        ->id('place-multi-day-id')
        ->data([
            'title' => 'Place Multi Day',
            'multi_day' => true,
            'location' => [
                'name' => 'Hall C',
            ],
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
        'event' => 'place-single-id',
    ]))->assertDownload('place-single.ics')->streamedContent();

    $recurringDate = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'place-recurring-id',
    ]))->assertDownload('place-recurring.ics')->streamedContent();

    $recurringWhole = $this->get(route('statamic.events.ics.show', [
        'event' => 'place-recurring-id',
    ]))->assertDownload('place-recurring.ics')->streamedContent();

    $multiDate = $this->get(route('statamic.events.ics.show', [
        'date' => now()->toDateString(),
        'event' => 'place-multi-day-id',
    ]))->assertDownload('place-multi-day.ics')->streamedContent();

    $multiWhole = $this->get(route('statamic.events.ics.show', [
        'event' => 'place-multi-day-id',
    ]))->assertDownload('place-multi-day.ics')->streamedContent();

    $this->assertStringContainsString('LOCATION:Hall A', $single);
    $this->assertStringContainsString('URL:https://zoom.us/j/single', $single);
    $this->assertStringContainsString('GEO:10;20', $single);
    $this->assertStringContainsString('LOCATION:Hall B', $recurringDate);
    $this->assertStringContainsString('URL:https://zoom.us/j/recurring', $recurringDate);
    $this->assertStringContainsString('LOCATION:Hall B', $recurringWhole);
    $this->assertStringContainsString('URL:https://zoom.us/j/recurring', $recurringWhole);
    $this->assertStringContainsString('LOCATION:Hall C', $multiDate);
    $this->assertStringContainsString('URL:https://zoom.us/j/multiday', $multiDate);
    expect(substr_count($multiWhole, 'LOCATION:Hall C'))->toBe(2)
        ->and(substr_count($multiWhole, 'URL:https://zoom.us/j/multiday'))->toBe(2);
});
