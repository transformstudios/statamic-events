<?php

namespace TransformStudios\Events\Tests\UpdateScripts;

use Mockery;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use TransformStudios\Events\UpdateScripts\MigrateLocationFields;

beforeEach(function () {
    $this->lines = [];
    $this->console = Mockery::mock(\Illuminate\Console\Command::class)->shouldIgnoreMissing();
    $this->console->shouldReceive('warn')->zeroOrMoreTimes()->andReturnUsing(function ($message) {
        $this->lines[] = $message;
    });
    $this->console->shouldReceive('line')->zeroOrMoreTimes()->andReturnUsing(function ($message) {
        $this->lines[] = $message;
    });
    $this->console->shouldReceive('info')->zeroOrMoreTimes();
    $this->script = new MigrateLocationFields('transformstudios/events', $this->console);
});

test('migrates address to location name', function () {
    Entry::make()
        ->collection('events')
        ->slug('address-event')
        ->id('address-id')
        ->data([
            'title' => 'Address Event',
            'start_date' => now()->toDateString(),
            'address' => '123 Main St',
        ])->save();

    $this->script->update();

    $entry = Entry::find('address-id');

    expect($entry->get('location'))->toBe(['name' => '123 Main St'])
        ->and($entry->get('address'))->toBeNull();
});

test('migrates non-URL string location to location name', function () {
    Entry::make()
        ->collection('events')
        ->slug('string-location-event')
        ->id('string-location-id')
        ->data([
            'title' => 'String Location Event',
            'start_date' => now()->toDateString(),
            'location' => 'City Hall',
        ])->save();

    $this->script->update();

    $entry = Entry::find('string-location-id');

    expect($entry->get('location'))->toBe(['name' => 'City Hall']);
});

test('migrates URL string location to online_url', function () {
    Entry::make()
        ->collection('events')
        ->slug('url-location-event')
        ->id('url-location-id')
        ->data([
            'title' => 'URL Location Event',
            'start_date' => now()->toDateString(),
            'location' => 'https://zoom.us/j/123',
        ])->save();

    $this->script->update();

    $entry = Entry::find('url-location-id');

    expect($entry->get('online_url'))->toBe('https://zoom.us/j/123')
        ->and($entry->get('location'))->toBeNull();
});

test('migrates link to online_url', function () {
    Entry::make()
        ->collection('events')
        ->slug('link-event')
        ->id('link-id')
        ->data([
            'title' => 'Link Event',
            'start_date' => now()->toDateString(),
            'link' => 'https://example.com/join',
        ])->save();

    $this->script->update();

    $entry = Entry::find('link-id');

    expect($entry->get('online_url'))->toBe('https://example.com/join')
        ->and($entry->get('link'))->toBeNull();
});

test('migrates top-level coordinates under location', function () {
    Entry::make()
        ->collection('events')
        ->slug('coords-event')
        ->id('coords-id')
        ->data([
            'title' => 'Coords Event',
            'start_date' => now()->toDateString(),
            'coordinates' => [
                'latitude' => 40,
                'longitude' => 50,
            ],
        ])->save();

    $this->script->update();

    $entry = Entry::find('coords-id');

    expect($entry->get('location'))->toBe([
        'coordinates' => [
            'latitude' => 40,
            'longitude' => 50,
        ],
    ])->and($entry->get('coordinates'))->toBeNull();
});

test('migrates address link and coordinates together', function () {
    Entry::make()
        ->collection('events')
        ->slug('combo-event')
        ->id('combo-id')
        ->data([
            'title' => 'Combo Event',
            'start_date' => now()->toDateString(),
            'address' => '123 Main St',
            'link' => 'https://example.com/join',
            'coordinates' => [
                'latitude' => 40,
                'longitude' => 50,
            ],
        ])->save();

    $this->script->update();

    $entry = Entry::find('combo-id');

    expect($entry->get('location'))->toBe([
        'name' => '123 Main St',
        'coordinates' => [
            'latitude' => 40,
            'longitude' => 50,
        ],
    ])
        ->and($entry->get('online_url'))->toBe('https://example.com/join')
        ->and($entry->get('address'))->toBeNull()
        ->and($entry->get('link'))->toBeNull()
        ->and($entry->get('coordinates'))->toBeNull();
});

test('skips when address and non-URL location both set', function () {
    Entry::make()
        ->collection('events')
        ->slug('ambiguous-address-event')
        ->id('ambiguous-address-id')
        ->data([
            'title' => 'Ambiguous Address Event',
            'start_date' => now()->toDateString(),
            'address' => '123 Main St',
            'location' => 'City Hall',
        ])->save();

    $this->script->update();

    $entry = Entry::find('ambiguous-address-id');

    expect($entry->get('address'))->toBe('123 Main St')
        ->and($entry->get('location'))->toBe('City Hall')
        ->and(implode("\n", $this->lines))->toContain('ambiguous-address-id');
});

test('skips when link and URL location both set', function () {
    Entry::make()
        ->collection('events')
        ->slug('ambiguous-link-event')
        ->id('ambiguous-link-id')
        ->data([
            'title' => 'Ambiguous Link Event',
            'start_date' => now()->toDateString(),
            'link' => 'https://example.com/join',
            'location' => 'https://zoom.us/j/123',
        ])->save();

    $this->script->update();

    $entry = Entry::find('ambiguous-link-id');

    expect($entry->get('link'))->toBe('https://example.com/join')
        ->and($entry->get('location'))->toBe('https://zoom.us/j/123')
        ->and(implode("\n", $this->lines))->toContain('ambiguous-link-id');
});

test('skips when online_url conflicts with link', function () {
    Entry::make()
        ->collection('events')
        ->slug('conflict-online-event')
        ->id('conflict-online-id')
        ->data([
            'title' => 'Conflict Online Event',
            'start_date' => now()->toDateString(),
            'online_url' => 'https://zoom.us/j/already',
            'link' => 'https://example.com/join',
        ])->save();

    $this->script->update();

    $entry = Entry::find('conflict-online-id');

    expect($entry->get('online_url'))->toBe('https://zoom.us/j/already')
        ->and($entry->get('link'))->toBe('https://example.com/join')
        ->and(implode("\n", $this->lines))->toContain('conflict-online-id');
});

test('leaves array-shaped location untouched', function () {
    Entry::make()
        ->collection('events')
        ->slug('prime-location-event')
        ->id('prime-location-id')
        ->data([
            'title' => 'Prime Location Event',
            'start_date' => now()->toDateString(),
            'location' => [
                'details' => 'Virtual',
                'coordinates' => [
                    'latitude' => 40,
                    'longitude' => 50,
                ],
            ],
            'link' => 'https://example.com/join',
        ])->save();

    $this->script->update();

    $entry = Entry::find('prime-location-id');

    expect($entry->get('location'))->toBe([
        'details' => 'Virtual',
        'coordinates' => [
            'latitude' => 40,
            'longitude' => 50,
        ],
    ])
        ->and($entry->get('online_url'))->toBe('https://example.com/join')
        ->and($entry->get('link'))->toBeNull();
});

test('does not save entries with nothing to migrate', function () {
    Entry::make()
        ->collection('events')
        ->slug('noop-event')
        ->id('noop-id')
        ->data([
            'title' => 'Noop Event',
            'start_date' => now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
        ])->save();

    $saved = 0;
    \Statamic\Facades\Entry::find('noop-id');
    \Illuminate\Support\Facades\Event::listen(\Statamic\Events\EntrySaved::class, function ($event) use (&$saved) {
        if ($event->entry->id() === 'noop-id') {
            $saved++;
        }
    });

    $this->script->update();

    expect($saved)->toBe(0)
        ->and(Entry::find('noop-id')->get('location'))->toBeNull()
        ->and(Entry::find('noop-id')->get('online_url'))->toBeNull();
});

test('migrates localized entries', function () {
    Site::setSites([
        'default' => ['name' => 'English', 'locale' => 'en_US', 'url' => '/'],
        'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => '/fr/'],
    ]);

    $this->collection->sites(['default', 'fr'])->save();

    $origin = Entry::make()
        ->collection('events')
        ->locale('default')
        ->slug('localized-event')
        ->id('localized-origin-id')
        ->data([
            'title' => 'Localized Event',
            'start_date' => now()->toDateString(),
            'address' => '123 Main St',
            'link' => 'https://example.com/en',
        ]);
    $origin->save();

    $localized = $origin->makeLocalization('fr');
    $localized->id('localized-fr-id');
    $localized->data([
        'title' => 'Événement',
        'address' => '456 Rue Principale',
        'link' => 'https://example.com/fr',
    ]);
    $localized->save();

    $this->script->update();

    expect(Entry::find('localized-origin-id')->get('location'))->toBe(['name' => '123 Main St'])
        ->and(Entry::find('localized-origin-id')->get('online_url'))->toBe('https://example.com/en')
        ->and(Entry::find('localized-fr-id')->get('location'))->toBe(['name' => '456 Rue Principale'])
        ->and(Entry::find('localized-fr-id')->get('online_url'))->toBe('https://example.com/fr');
});
