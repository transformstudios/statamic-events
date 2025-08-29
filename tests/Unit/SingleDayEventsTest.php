<?php

uses(\TransformStudios\Events\Tests\TestCase::class);
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonTimeZone;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Types\SingleDayEvent;


test('can create single event', function () {
    $entry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'timezone' => 'America/Vancouver',
        ]);

    $event = EventFactory::createFromEntry($entry);

    expect($event instanceof SingleDayEvent)->toBeTrue();
    expect($event->isRecurring())->toBeFalse();
    expect($event->isMultiDay())->toBeFalse();
    expect($event->hasEndTime())->toBeTrue();
    expect($event->start()->timezone)->toEqual(new CarbonTimeZone('America/Vancouver'));
    expect($event->end()->timezone)->toEqual(new CarbonTimeZone('America/Vancouver'));
});

test('can create single all day event', function () {
    $entry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => Carbon::now()->toDateString(),
            'all_day' => true,
        ]);

    $event = EventFactory::createFromEntry($entry);

    expect($event instanceof SingleDayEvent)->toBeTrue();
    expect($event->isAllDay())->toBeTrue();
});

test('end is end of day when no end time', function () {
    Carbon::setTestNow(now());

    $entry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
        ]);

    $event = EventFactory::createFromEntry($entry);

    expect($event->endTime())->toEqual('23:59:59');
    $nextOccurrences = $event->nextOccurrences();

    expect($nextOccurrences[0]->has_end_time)->toBeFalse();
    expect($nextOccurrences[0]->end)->toEqual(now()->endOfDay()->setMicrosecond(0));
});

test('empty occurrences if now after end date', function () {
    $recurringEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
        ]);

    $event = EventFactory::createFromEntry($recurringEntry);

    Carbon::setTestNow(now()->addDays(1));
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
        ]);

    $event = EventFactory::createFromEntry($recurringEntry);

    Carbon::setTestNow($startDate->setTimeFromTimeString('10:59:00'));

    $nextOccurrences = $event->nextOccurrences(2);

    expect($nextOccurrences)->toHaveCount(1);

    expect($nextOccurrences->first()->start)->toEqual($startDate);
});

test('can generate next occurrence if now is during', function () {
    $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00');
    $single = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
        ]);

    $singleNoEndTime = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
        ]);

    Carbon::setTestNow($startDate->addMinutes(10));

    $event = EventFactory::createFromEntry($single);
    $noEndTimeEvent = EventFactory::createFromEntry($singleNoEndTime);
    $nextOccurrences = $event->nextOccurrences();

    expect($nextOccurrences[0]->start)->toEqual($startDate);
    expect($noEndTimeEvent->nextOccurrences()[0]->start)->toEqual($startDate);
});

test('can supplement no end time', function () {
    $startDate = CarbonImmutable::now()->setTimeFromTimeString('11:00');
    $noEndTimeEntry = Entry::make()
        ->collection('events')
        ->data([
            'start_date' => $startDate->toDateString(),
            'start_time' => '11:00',
        ]);

    Carbon::setTestNow($startDate->addMinutes(10));

    $event = EventFactory::createFromEntry($noEndTimeEntry);
    $nextOccurrences = $event->nextOccurrences();

    expect($nextOccurrences[0]->has_end_time)->toBeFalse();
});
