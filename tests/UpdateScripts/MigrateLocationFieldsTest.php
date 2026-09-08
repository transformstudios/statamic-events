<?php

use Statamic\Facades\Entry;
use Statamic\Testing\Concerns\RunsUpdateScripts;
use TransformStudios\Events\UpdateScripts\MigrateLocationFields;

uses(RunsUpdateScripts::class);

beforeEach(function () {
    $this->assertUpdateScriptRegistered(MigrateLocationFields::class);
});

test('migrates address to location', function () {
    Entry::make()
        ->collection('events')
        ->slug('address-event')
        ->id('address-id')
        ->data([
            'title' => 'Address Event',
            'start_date' => now()->toDateString(),
            'address' => '123 Main St',
        ])->save();

    $this->runUpdateScript(MigrateLocationFields::class);

    $entry = Entry::find('address-id');

    expect($entry->get('location'))->toBe('123 Main St')
        ->and($entry->get('address'))->toBeNull();
});

test('migrates link to online_url', function () {
    Entry::make()
        ->collection('events')
        ->slug('link-event')
        ->id('link-id')
        ->data([
            'title' => 'Link Event',
            'start_date' => now()->toDateString(),
            'link' => 'https://zoom.us/j/123',
        ])->save();

    $this->runUpdateScript(MigrateLocationFields::class);

    $entry = Entry::find('link-id');

    expect($entry->get('online_url'))->toBe('https://zoom.us/j/123')
        ->and($entry->get('link'))->toBeNull();
});

test('migrates url-valued location to online_url', function () {
    Entry::make()
        ->collection('events')
        ->slug('url-location-event')
        ->id('url-location-id')
        ->data([
            'title' => 'URL Location Event',
            'start_date' => now()->toDateString(),
            'location' => 'https://zoom.us/j/123',
        ])->save();

    $this->runUpdateScript(MigrateLocationFields::class);

    $entry = Entry::find('url-location-id');

    expect($entry->get('online_url'))->toBe('https://zoom.us/j/123')
        ->and($entry->get('location'))->toBeNull();
});

test('migrates address and url-valued location together', function () {
    Entry::make()
        ->collection('events')
        ->slug('address-url-event')
        ->id('address-url-id')
        ->data([
            'title' => 'Address URL Event',
            'start_date' => now()->toDateString(),
            'address' => '123 Main St',
            'location' => 'https://zoom.us/j/123',
        ])->save();

    $this->runUpdateScript(MigrateLocationFields::class);

    $entry = Entry::find('address-url-id');

    expect($entry->get('location'))->toBe('123 Main St')
        ->and($entry->get('online_url'))->toBe('https://zoom.us/j/123')
        ->and($entry->get('address'))->toBeNull();
});

test('leaves non-url string location untouched', function () {
    Entry::make()
        ->collection('events')
        ->slug('place-event')
        ->id('place-id')
        ->data([
            'title' => 'Place Event',
            'start_date' => now()->toDateString(),
            'location' => 'Outside the side exit',
        ])->save();

    $this->runUpdateScript(MigrateLocationFields::class);

    expect(Entry::find('place-id')->get('location'))->toBe('Outside the side exit');
});

test('skips array-shaped location completely', function () {
    Entry::make()
        ->collection('events')
        ->slug('group-location-event')
        ->id('group-location-id')
        ->data([
            'title' => 'Group Location Event',
            'start_date' => now()->toDateString(),
            'location' => [
                'details' => 'Virtual',
            ],
            'address' => '123 Main St',
            'link' => 'https://zoom.us/j/123',
        ])->save();

    $this->runUpdateScript(MigrateLocationFields::class);

    $entry = Entry::find('group-location-id');

    expect($entry->get('location'))->toBe([
        'details' => 'Virtual',
    ])
        ->and($entry->get('address'))->toBe('123 Main St')
        ->and($entry->get('link'))->toBe('https://zoom.us/j/123')
        ->and($entry->get('online_url'))->toBeNull();
});

test('skips address and non-url location conflict', function () {
    Entry::make()
        ->collection('events')
        ->slug('address-conflict-event')
        ->id('address-conflict-id')
        ->data([
            'title' => 'Address Conflict Event',
            'start_date' => now()->toDateString(),
            'address' => '123 Main St',
            'location' => 'The Hall',
        ])->save();

    $this->runUpdateScript(MigrateLocationFields::class);

    $entry = Entry::find('address-conflict-id');

    expect($entry->get('address'))->toBe('123 Main St')
        ->and($entry->get('location'))->toBe('The Hall');
});

test('skips link and url-valued location conflict', function () {
    Entry::make()
        ->collection('events')
        ->slug('link-conflict-event')
        ->id('link-conflict-id')
        ->data([
            'title' => 'Link Conflict Event',
            'start_date' => now()->toDateString(),
            'link' => 'https://zoom.us/j/111',
            'location' => 'https://zoom.us/j/222',
        ])->save();

    $this->runUpdateScript(MigrateLocationFields::class);

    $entry = Entry::find('link-conflict-id');

    expect($entry->get('link'))->toBe('https://zoom.us/j/111')
        ->and($entry->get('location'))->toBe('https://zoom.us/j/222')
        ->and($entry->get('online_url'))->toBeNull();
});
