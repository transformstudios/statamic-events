<?php

namespace TransformStudios\Events\Tests\Types;

use Carbon\Carbon;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events;
use TransformStudios\Events\Types\RecurringEvent;
use TransformStudios\Events\Types\SingleDayEvent;

test('can create recurring event', function () {
    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'recurrence' => 'daily',
        ]);

    $event = EventFactory::createFromEntry($recurringEntry);

    expect($event instanceof RecurringEvent)->toBeTrue();
    expect($event->isRecurring())->toBeTrue();
    expect($event->isMultiDay())->toBeFalse();
});

test('wont create recurring event when multi day', function () {
    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'recurrence' => 'multi_day',
        ]);

    $event = EventFactory::createFromEntry($recurringEntry);

    expect($event instanceof SingleDayEvent)->toBeTrue();
    expect($event->isRecurring())->toBeFalse();
    expect($event->isMultiDay())->toBeFalse();
});

test('can show last occurrence when no end time', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $recurringEntry = tap(Entry::make()
        ->collection('events')
        ->data([
            'start_date' => Carbon::now()->addDays(1)->toDateString(),
            'start_time' => '22:00',
            'recurrence' => 'daily',
            'end_date' => Carbon::now()->addDays(2)->toDateString(),
            'timezone' => 'America/Chicago',
        ]))->save();

    $occurrences = Events::fromCollection(handle: 'events')
        ->between(Carbon::now(), Carbon::now()->addDays(5)->endOfDay());

    expect($occurrences)->toHaveCount(2);
});

test('can generate monthly by day occurrences', function () {
    Carbon::setTestNow(Carbon::parse('Jan 29 2025 10:00am'));

    $recurringEntry = tap(Entry::make()
        ->collection('events')
        ->data([
            'start_date' => Carbon::now()->addDays(1)->toDateString(),
            'start_time' => '22:00',
            'recurrence' => 'monthly',
            'end_date' => Carbon::now()->addMonths(3)->toDateString(),
            'timezone' => 'America/Chicago',
            'specific_days' => ['first_sunday', 'last_wednesday'],
        ]))->save();

    $occurrences = Events::fromCollection(handle: 'events')
        ->between(Carbon::now(), Carbon::now()->addMonths(4));

    expect($occurrences)->toHaveCount(5);
});
