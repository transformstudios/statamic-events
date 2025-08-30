<?php

use Carbon\Carbon;
use Statamic\Facades\Entry;
use TransformStudios\Events\EventFactory;
use TransformStudios\Events\Types\MultiDayEvent;

beforeEach(function () {
    Carbon::setTestNowAndTimezone(now(), 'America/Vancouver');

    $entry = Entry::make()
        ->slug('multi-day-event')
        ->collection('events')
        ->data([
            'recurrence' => 'multi_day',
            'days' => [
                [
                    'date' => '2019-11-24',
                    'start_time' => '11:00',
                    'end_time' => '15:00',
                ],
                [
                    'date' => '2019-11-25',
                    'start_time' => '11:00',
                    'end_time' => '15:00',
                ],
                [
                    'date' => '2019-11-23',
                    'start_time' => '19:00',
                    'end_time' => '21:00',
                ],
            ],
            'timezone' => 'America/Vancouver',
        ]);

    $this->event = EventFactory::createFromEntry($entry);

    $noEndTimeEntry = Entry::make()
        ->collection('events')
        ->slug('no-end-time')
        ->data([
            'recurrence' => 'multi_day',
            'days' => [
                [
                    'date' => '2019-11-23',
                    'start_time' => '19:00',
                ],
                [
                    'date' => '2019-11-24',
                    'start_time' => '15:00',
                ],
            ],
            'timezone' => 'America/Vancouver',
        ]);

    $this->noEndTimeEvent = EventFactory::createFromEntry($noEndTimeEntry);

    $allDayEntry = Entry::make()
        ->collection('events')
        ->data([
            'recurrence' => 'multi_day',
            'days' => [
                [
                    'date' => '2019-11-20',
                ],
                [
                    'date' => '2019-11-21',
                ],
            ],
            'timezone' => 'America/Vancouver',
        ]);
    $this->allDayEvent = EventFactory::createFromEntry($allDayEntry);
});

test('can create multi day event', function () {
    expect($this->event instanceof MultiDayEvent)->toBeTrue();
    expect($this->allDayEvent instanceof MultiDayEvent)->toBeTrue();
    expect($this->noEndTimeEvent instanceof MultiDayEvent)->toBeTrue();
    expect($this->event->isMultiDay())->toBeTrue();
    expect($this->allDayEvent->isMultiDay())->toBeTrue();
    expect($this->noEndTimeEvent->isMultiDay())->toBeTrue();
});

test('can get start', function () {
    expect($this->event->start())->toEqual(Carbon::parse('2019-11-23 19:00')->shiftTimezone('America/Vancouver'));
    expect($this->allDayEvent->start())->toEqual(Carbon::parse('2019-11-20 0:00')->shiftTimezone('America/Vancouver'));
    expect($this->event->start()->timezone)->toEqual(Carbon::parse('2019-11-20 0:00')->shiftTimezone('America/Vancouver')->timezone);
});

test('can get end', function () {
    expect($this->event->end())->toEqual(Carbon::parse('2019-11-25 15:00')->shiftTimezone('America/Vancouver'));
    expect($this->allDayEvent->end())->toEqual(Carbon::parse('2019-11-21 23:59:59.999999')->shiftTimezone('America/Vancouver'));
    expect($this->event->end()->timezone)->toEqual(Carbon::parse('2019-11-21 23:59:00')->shiftTimezone('America/Vancouver')->timezone);
});

test('no occurrences if now after end date', function () {
    Carbon::setTestNow('2019-11-26');
    expect($this->event->nextOccurrences(1))->toBeEmpty();
});

test('can generate next occurrence if before', function () {
    Carbon::setTestNowAndTimezone('2019-11-22', 'America/Vancouver');

    expect($this->event->nextOccurrences()[0]->start)->toEqual(Carbon::parse('2019-11-23')->setTimeFromTimeString('19:00:00'));
    expect($this->event->nextOccurrences()[0]->end)->toEqual(Carbon::parse('2019-11-23')->setTimeFromTimeString('21:00'));
});

test('can generate next occurrence if during', function () {
    Carbon::setTestNowAndTimezone('2019-11-24 10:00', 'America/Vancouver');
    expect($this->event->nextOccurrences()[0]->start)->toEqual(Carbon::parse('2019-11-24')->setTimeFromTimeString('11:00:00'));
});

test('day is all day when no start and end time', function () {
    $days = $this->allDayEvent->days();

    expect($days[0]->isAllDay())->toBeTrue();
});
