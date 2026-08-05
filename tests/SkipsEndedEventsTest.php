<?php

namespace TransformStudios\Events\Tests;

use Illuminate\Support\Carbon;
use Statamic\Facades\Entry;
use TransformStudios\Events\Events;

beforeEach(function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));
});

test('keeps unbounded recurring event that started years ago', function () {
    Entry::make()
        ->collection('events')
        ->slug('weekly-halaqa')
        ->data([
            'title' => 'Weekly Halaqa',
            'start_date' => now()->subYears(2)->toDateString(),
            'start_time' => '19:00',
            'end_time' => '20:00',
            'recurrence' => 'weekly',
        ])->save();

    expect(Events::fromCollection('events')->upcoming(1))->toHaveCount(1);
});

test('keeps annually recurring event that started years ago', function () {
    Entry::make()
        ->collection('events')
        ->slug('annual-fundraiser')
        ->data([
            'title' => 'Annual Fundraiser',
            'start_date' => now()->subYears(3)->toDateString(),
            'start_time' => '18:00',
            'end_time' => '21:00',
            'recurrence' => 'every',
            'interval' => 1,
            'period' => 'years',
        ])->save();

    expect(Events::fromCollection('events')->upcoming(1))->toHaveCount(1);
});

test('keeps recurring event whose end date is still in the future', function () {
    Entry::make()
        ->collection('events')
        ->slug('term-class')
        ->data([
            'title' => 'Term Class',
            'start_date' => now()->subYear()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'recurrence' => 'weekly',
            'end_date' => now()->addMonths(2)->toDateString(),
        ])->save();

    expect(Events::fromCollection('events')->upcoming(1))->toHaveCount(1);
});

test('drops recurring event whose end date has passed', function () {
    Entry::make()
        ->collection('events')
        ->slug('finished-class')
        ->data([
            'title' => 'Finished Class',
            'start_date' => now()->subYears(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'recurrence' => 'weekly',
            'end_date' => now()->subYear()->toDateString(),
        ])->save();

    expect(Events::fromCollection('events')->upcoming(1))->toBeEmpty();
});

test('drops single day event in the past but keeps one in the future', function () {
    Entry::make()
        ->collection('events')
        ->slug('past-lecture')
        ->data([
            'title' => 'Past Lecture',
            'start_date' => now()->subMonth()->toDateString(),
            'start_time' => '19:00',
        ])->save();

    Entry::make()
        ->collection('events')
        ->slug('future-lecture')
        ->data([
            'title' => 'Future Lecture',
            'start_date' => now()->addMonth()->toDateString(),
            'start_time' => '19:00',
        ])->save();

    $occurrences = Events::fromCollection('events')->upcoming(1);

    expect($occurrences)->toHaveCount(1);
    expect($occurrences->first()->title)->toBe('Future Lecture');
});

test('drops multi day event whose days have all passed but keeps one with a day still to come', function () {
    Entry::make()
        ->collection('events')
        ->slug('past-conference')
        ->data([
            'title' => 'Past Conference',
            'recurrence' => 'multi_day',
            'days' => [
                ['date' => now()->subMonth()->toDateString(), 'start_time' => '09:00', 'end_time' => '17:00'],
                ['date' => now()->subMonth()->addDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '17:00'],
            ],
        ])->save();

    Entry::make()
        ->collection('events')
        ->slug('upcoming-conference')
        ->data([
            'title' => 'Upcoming Conference',
            'recurrence' => 'multi_day',
            'days' => [
                ['date' => now()->addMonth()->toDateString(), 'start_time' => '09:00', 'end_time' => '17:00'],
                ['date' => now()->addMonth()->addDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '17:00'],
            ],
        ])->save();

    $occurrences = Events::fromCollection('events')->upcoming(5);

    expect($occurrences)->not->toBeEmpty();
    expect($occurrences->pluck('title')->unique()->all())->toBe(['Upcoming Conference']);
});

test('keeps a multi day event that is partway through', function () {
    Entry::make()
        ->collection('events')
        ->slug('current-conference')
        ->data([
            'title' => 'Current Conference',
            'recurrence' => 'multi_day',
            'days' => [
                ['date' => now()->subDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '17:00'],
                ['date' => now()->addDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '17:00'],
            ],
        ])->save();

    expect(Events::fromCollection('events')->upcoming(5))->not->toBeEmpty();
});

test('past events are still returned when explicitly querying a past range', function () {
    Entry::make()
        ->collection('events')
        ->slug('last-years-lecture')
        ->data([
            'title' => 'Last Years Lecture',
            'start_date' => now()->subYear()->toDateString(),
            'start_time' => '19:00',
            'end_time' => '20:00',
        ])->save();

    $occurrences = Events::fromCollection('events')->between(
        now()->subYear()->startOfDay(),
        now()->subYear()->endOfDay(),
    );

    expect($occurrences)->toHaveCount(1);
});
