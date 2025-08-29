<?php

use Illuminate\Support\Carbon;
use TransformStudios\Events\Day;

test('can get end when no end time', function () {
    $dayData = [
        'date' => '2019-11-23',
        'start_time' => '19:00',
    ];

    $day = new Day(data: $dayData, timezone: 'America/Vancouver');

    expect($day->end())->toEqual(Carbon::parse('2019-11-23')->shiftTimezone('America/Vancouver')->endOfDay());
});

test('has no end time when no end time', function () {
    $dayData = [
        'date' => '2019-11-23',
        'start_time' => '19:00',
    ];

    $day = new Day(data: $dayData, timezone: 'America/Vancouver');

    expect($day->hasEndTime())->toBeFalse();
});
