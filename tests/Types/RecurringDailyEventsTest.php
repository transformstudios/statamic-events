<?php

namespace TransformStudios\Events\Tests\Types;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Events;

test('null next date if now after end date', function () {
    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'end_date' => Carbon::now()->addDays(2)->toDateString(),
            'recurrence' => 'daily',
        ]);

    $event = EventFactory::createFromEntry($recurringEntry);

    Carbon::setTestNow(now()->addDays(3));
    $nextOccurrences = $event->nextOccurrences();

    expect($nextOccurrences)->toBeEmpty();
});

test('can generate next day if now is before', function () {
    $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00');

    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
        ]);

    $event = EventFactory::createFromEntry($recurringEntry);

    Carbon::setTestNow($startDate->setTimeFromTimeString('10:59:00'));

    $nextOccurrences = $event->nextOccurrences(3);

    expect($nextOccurrences)->toHaveCount(3);

    expect($nextOccurrences->first()->start)->toEqual($startDate);
});

test('can generate next occurrence if now is during', function () {
    $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00');
    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
        ]);

    Carbon::setTestNow($startDate->addMinutes(10));

    $event = EventFactory::createFromEntry($recurringEntry);
    $nextOccurrences = $event->nextOccurrences();

    expect($nextOccurrences[0]->start)->toEqual($startDate);
});

test('app and event in different timezone ', function () {
    $startDate = CarbonImmutable::createFromDate(2026, 2, 15);
    Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $startDate->toDateString(),
            'timezone' => 'America/Los_Angeles',
            'start_time' => '05:00',
            'end_time' => '16:00',
            'recurrence' => 'monthly',
            'specific_days' => ['third_monday'],
        ])->save();

    $events = Events::fromCollection('events')
        ->between(
            CarbonImmutable::createFromDate(2026, 2, 15)->startOfDay(),
            CarbonImmutable::createFromDate(2026, 3, 16)->endOfDay()
        );

    expect($events)->toHaveCount(2);
});
