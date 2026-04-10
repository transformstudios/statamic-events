<?php

namespace TransformStudios\Events\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery;
use Statamic\Addons\Addon;
use Statamic\Addons\FileSettings;
use Statamic\Extensions\Pagination\LengthAwarePaginator;
use Statamic\Facades\Addon as AddonFacade;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events;

test('can generate dates when now before start', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('recurring-event')
        ->data([
            'title' => 'Recurring Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'end_date' => Carbon::now()->addDays(2)->toDateString(),
            'recurrence' => 'daily',
        ])->save();

    $event = tap(Entry::make()
        ->collection('events')
        ->slug('single-event')
        ->data([
            'title' => 'Single Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '13:00',
        ]))->save();

    $occurrences = Events::fromCollection(handle: 'events')
        ->between(now(), now()->addDays(2)->endOfDay());

    $expectedStartDates = [
        now()->setTimeFromTimeString('11:00'),
        now()->setTimeFromTimeString('13:00'),
        now()->addDay()->setTimeFromTimeString('11:00'),
        now()->addDays(2)->setTimeFromTimeString('11:00'),
    ];
    expect($occurrences)->toHaveCount(4);

    expect($occurrences[0]->start)->toEqual($expectedStartDates[0]);
    expect($occurrences[1]->start)->toEqual($expectedStartDates[1]);
    expect($occurrences[2]->start)->toEqual($expectedStartDates[2]);
    expect($occurrences[3]->start)->toEqual($expectedStartDates[3]);
});

test('can paginate upcoming occurrences', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('recurring-event')
        ->data([
            'title' => 'Recurring Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
        ])->save();

    $occurrences = Events::fromCollection(handle: 'events')
        ->upcoming(10);

    expect($occurrences)->toHaveCount(10);
    $paginator = Events::fromCollection(handle: 'events')
        ->pagination(perPage: 2)
        ->upcoming(10);

    expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($occurrences = $paginator->items())->toHaveCount(2);
    expect($paginator->items()[1]->start)->toEqual(now()->addDay()->setTimeFromTimeString('11:00'));

    $paginator = Events::fromCollection(handle: 'events')
        ->pagination(perPage: 3, page: 3)
        ->upcoming(10);

    expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($occurrences = $paginator->items())->toHaveCount(3);
    expect($paginator->currentPage())->toEqual(3);
});

test('can paginate occurrences between', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('recurring-event')
        ->data([
            'title' => 'Recurring Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
        ])->save();

    $occurrences = Events::fromCollection(handle: 'events')
        ->between(now(), now()->addDays(9)->endOfDay());

    expect($occurrences)->toHaveCount(10);
    $paginator = Events::fromCollection(handle: 'events')
        ->pagination(perPage: 2)
        ->between(now(), now()->addDays(9)->endOfDay());

    expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class);

    expect($occurrences = $paginator->items())->toHaveCount(2);

    expect($paginator->items()[1]->start)->toEqual(now()->addDay()->setTimeFromTimeString('11:00'));
});

test('can filter events', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('recurring-event')
        ->data([
            'title' => 'Recurring Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
        ])->save();

    Entry::make()
        ->collection('events')
        ->slug('other-event')
        ->data([
            'title' => 'Other Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
        ])->save();

    $occurrences = Events::fromCollection(handle: 'events')
        ->filter('title:contains', 'Other')
        ->between(now(), now()->addDays(9)->endOfDay());

    expect($occurrences)->toHaveCount(10);
});

test('can filter multiple events', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('recurring-event')
        ->data([
            'title' => 'Recurring Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
        ])->save();

    Entry::make()
        ->collection('events')
        ->slug('other-event')
        ->data([
            'title' => 'Other Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
        ])->save();

    Entry::make()
        ->collection('events')
        ->slug('other-event')
        ->data([
            'title' => 'Other Event 2',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'weekly',
        ])->save();

    $occurrences = Events::fromCollection(handle: 'events')
        ->filter('title:contains', 'Other')
        ->filter('recurrence:is', 'daily')
        ->between(now(), now()->addDays(9)->endOfDay());

    expect($occurrences)->toHaveCount(10);
});

test('can filter by term events', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
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

    Entry::make()
        ->collection('events')
        ->slug('other-event')
        ->data([
            'title' => 'Other Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
            'categories' => ['two'],
        ])->save();

    $occurrences = Events::fromCollection(handle: 'events')
        ->terms('categories::two')
        ->between(now(), now()->addDays(9)->endOfDay());

    expect($occurrences)->toHaveCount(10);
});

test('can filter by filter events', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('recurring-event')
        ->data([
            'title' => 'Recurring Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
        ])->save();

    Entry::make()
        ->collection('events')
        ->slug('other-event')
        ->data([
            'title' => 'Other Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
        ])->save();

    $occurrences = Events::fromCollection(handle: 'events')
        ->filter('title:contains', 'Recurring')
        ->between(now(), now()->addDays(9)->endOfDay());

    expect($occurrences)->toHaveCount(10);
});

test('can determine occurs at for single event', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $entry = Entry::make()
        ->collection('events')
        ->slug('single-event')
        ->id('the-id')
        ->data([
            'title' => 'Single Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
        ]);

    $event = EventFactory::createFromEntry($entry);

    expect($event->occursOnDate(now()))->toBeTrue();
});

test('can determine occurs at for multiday event', function () {
    Carbon::setTestNow(now());

    $entry = Entry::make()
        ->slug('multi-day-event')
        ->collection('events')
        ->data([
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
        ]);

    $event = EventFactory::createFromEntry($entry);

    expect($event->occursOnDate(now()->subDay()))->toBeFalse();
    expect($event->occursOnDate(now()))->toBeTrue();
    expect($event->occursOnDate(now()->addDay()))->toBeTrue();
    expect($event->occursOnDate(now()->addDays(2)))->toBeTrue();
    expect($event->occursOnDate(now()->addDays(3)))->toBeFalse();
});

test('can exclude dates', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('recurring-event')
        ->data([
            'title' => 'Recurring Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
            'exclude_dates' => [['date' => Carbon::now()->addDay()->toDateString()]],
        ])->save();

    $occurrences = Events::fromCollection(handle: 'events')
        ->between(now(), now()->addDays(3)->endOfDay());

    expect($occurrences)->toHaveCount(3);
});

test('can handle empty exclude dates', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('recurring-event')
        ->data([
            'title' => 'Recurring Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
            'exclude_dates' => [['id' => 'random-id']],
        ])->save();

    $occurrences = Events::fromCollection(handle: 'events')
        ->between(now(), now()->addDays(3)->endOfDay());

    expect($occurrences)->toHaveCount(4);
});

test('can filter our events with no start date', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('single-event')
        ->data([
            'title' => 'Single Event',
            'start_time' => '11:00',
            'end_time' => '12:00',
        ])->save();
    Entry::make()
        ->collection('events')
        ->slug('legacy-multi-day-event')
        ->data([
            'title' => 'Legacy Multi-day Event',
            'multi_day' => true,
            'days' => [
                ['date' => 'bad-date'],
            ],
        ])->save();
    Entry::make()
        ->collection('events')
        ->slug('legacy-multi-day-event-2')
        ->data([
            'title' => 'Legacy Multi-day Event',
            'multi_day' => true,
        ])->save();

    $occurrences = Events::fromCollection(handle: 'events')
        ->upcoming(5);

    expect($occurrences)->toBeEmpty();
});

test('app and event in same timezone ', function () {
    $date = CarbonImmutable::createFromDate(2026, 2, 28);
    Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $date->toDateString(),
            'start_time' => '05:00',
            'end_time' => '23:00',
        ])->save();

    $events1 = Events::fromCollection('events')->between($date->startOfMonth(), $date->endOfMonth());
    $events2 = Events::fromCollection('events')->between($date->startOfMonth(), $date->endOfMonth()->endOfWeek());

    expect($events1)->toHaveCount(1);
    expect($events2)->toHaveCount(1);
});

test('app and event in different timezone', function () {
    $date = CarbonImmutable::createFromDate(2026, 2, 28);
    Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $date->toDateString(),
            'timezone' => 'America/Los_Angeles',
            'start_time' => '05:00',
            'end_time' => '23:00',
        ])->save();

    $events1 = Events::fromCollection('events')->between($date->startOfMonth(), $date->endOfMonth());
    $events2 = Events::fromCollection('events')->between($date->startOfMonth(), $date->endOfMonth()->endOfWeek());

    expect($events1)->toHaveCount(1);
    expect($events2)->toHaveCount(1);
});

test('event with timezone offset appears on the correct UTC date', function () {
    $date = CarbonImmutable::createFromDate(2026, 2, 28);
    Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $date->toDateString(),
            'timezone' => 'America/Los_Angeles',
            'start_time' => '22:00',
            'end_time' => '23:00',
        ])->save();

    $events1 = Events::fromCollection('events')->between($date->startOfDay(), $date->endOfDay());
    $events2 = Events::fromCollection('events')->between($date->startOfDay()->addDay(), $date->endOfDay()->addDay());

    expect($events1)->toHaveCount(0);
    expect($events2)->toHaveCount(1);
});

it('uses UTC when no app timezone set', function () {
    expect(Events::defaultTimezone())->toBe('UTC');
});

it('uses app timezone when no display_timezone set', function () {
    Config::set('app.timezone', 'America/New_York');

    expect(Events::defaultTimezone())->toBe('America/New_York');
});

it('uses display timezone when no events timezone set', function () {
    Config::set('statamic.system.display_timezone', 'America/Chicago');

    expect(Events::defaultTimezone())->toBe('America/Chicago');
});

it('uses addon setting for default timezone', function () {
    $eventsAddon = Addon::make('transformstudios/events');
    $settings = new FileSettings($eventsAddon, ['timezone' => 'Australia/Adelaide']);

    $addon = Mockery::mock();
    $addon->shouldReceive('settings')->andReturn($settings);

    AddonFacade::shouldReceive('get')->with('transformstudios/events')->andReturn($addon);

    expect(Events::defaultTimezone())->toBe('Australia/Adelaide');
});
