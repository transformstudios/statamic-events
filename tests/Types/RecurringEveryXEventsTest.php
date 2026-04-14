<?php

namespace TransformStudios\Events\Tests\Types;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Types\RecurringEvent;

test('can create every xevent', function () {
    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'weeks',
        ]);

    $event = EventFactory::createFromEntry($recurringEntry);

    expect($event)->toBeInstanceOf(RecurringEvent::class);
});

test('no occurences when now after end date', function () {
    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'end_date' => Carbon::now()->addDays(2)->toDateString(),
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'days',
        ]);

    $event = EventFactory::createFromEntry($recurringEntry);

    Carbon::setTestNow(now()->addDays(3));
    $nextOccurrences = $event->nextOccurrences();

    expect($nextOccurrences)->toBeEmpty();
});

test('can generate occurrence if now before', function () {
    $startDate = Carbon::now()->addDay()->setTimeFromTimeString('11:00');
    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'days',
        ]);

    $occurrences = EventFactory::createFromEntry($recurringEntry)
        ->nextOccurrences(1);

    expect($occurrences)->toHaveCount(1);

    expect($occurrences[0]->start)->toEqual($startDate);

    Carbon::setTestNow(now()->setTimeFromTimeString('10:59:59'));
    $occurrences = EventFactory::createFromEntry($recurringEntry)
        ->nextOccurrences(1);

    expect($occurrences[0]->start)->toEqual($startDate);
});

test('can generate occurrence if during', function () {
    $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00');
    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'days',
        ]);

    Carbon::setTestNow($startDate->addMinutes(10));
    $occurrences = EventFactory::createFromEntry($recurringEntry)
        ->nextOccurrences(1);

    expect($occurrences[0]->start)->toEqual($startDate);
});

test('can generate occurrence if now after first date', function () {
    $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00:00');

    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'days',
        ]);

    Carbon::setTestNow($startDate->addMinutes(1)->addHour());

    $event = EventFactory::createFromEntry($recurringEntry);

    $occurrences = $event->nextOccurrences(1);

    expect($occurrences[0]->start)->toEqual($startDate->addDays(2));

    // $nextDate = $event->upcomingDate(Carbon::now()->addDays(2));
    // $this->assertEquals($startDate, $nextDate->start());
});

test('can generate next occurrence in weeks if now after start', function () {
    $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00:00');

    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'weeks',
        ]);

    Carbon::setTestNow($startDate->addHours(2));

    $event = EventFactory::createFromEntry($recurringEntry);

    $occurrences = $event->nextOccurrences(1);

    expect($occurrences[0]->start)->toEqual($startDate->addWeeks(2));
});

test('can generate next occurrence if now after weeks', function () {
    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => '2021-01-18',
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'weeks',
        ]);

    $event = EventFactory::createFromEntry($recurringEntry);

    Carbon::setTestNow(Carbon::parse('2021-03-04')->setTimeFromTimeString('11:00:00'));

    $occurrences = $event->nextOccurrences(1);

    expect($occurrences)->not->toBeEmpty();

    expect($occurrences[0]->start)->toEqual(Carbon::parse('2021-03-15')->setTimeFromTimeString('11:00:00'));
});

test('can generate next occurrence if now during months', function () {
    $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00:00');

    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'months',
        ]);

    Carbon::setTestNow($startDate->addMinutes(1));

    $event = EventFactory::createFromEntry($recurringEntry);

    $occurrences = $event->nextOccurrences(1);

    expect($occurrences[0]->start)->toEqual($startDate->setTimeFromTimeString('11:00:00'));
});

test('can generate next xoccurrences from today before event time', function () {
    $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00:00');
    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'days',
        ]);
    $event = EventFactory::createFromEntry($recurringEntry);

    for ($x = 0; $x < 2; $x++) {
        $events[] = $startDate->addDays($x * 2);
    }

    Carbon::setTestNow($startDate->subMinutes(1));

    $occurrences = $event->nextOccurrences(2);

    expect($occurrences)->toHaveCount(2);

    expect($occurrences[0]->start)->toEqual($events[0]);
    expect($occurrences[1]->start)->toEqual($events[1]);
});

test('can generate all occurrences when after start date daily', function () {
    $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00:00');

    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $startDate->addDay()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'end_date' => $startDate->addDays(5)->toDateString(),
            'recurrence' => 'every',
            'interval' => 2,
            'period' => 'days',
        ]);

    for ($x = 1; $x <= 2; $x++) {
        $events[] = $startDate->addDays($x * 2 + 1);
    }

    $event = EventFactory::createFromEntry($recurringEntry);

    Carbon::setTestNow($startDate->addDays(1)->addHour(2));
    $occurrences = $event->nextOccurrences(5);

    expect($occurrences)->toHaveCount(2);

    expect($occurrences[0]->start)->toEqual($events[0]);
    expect($occurrences[1]->start)->toEqual($events[1]);
});
