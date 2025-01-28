<?php

uses(\TransformStudios\Events\Tests\TestCase::class);
use Illuminate\Support\Carbon;
use Statamic\Facades\Cascade;
use Statamic\Facades\Entry;
use TransformStudios\Events\Tags\Events;

uses(\TransformStudios\Events\Tests\PreventSavingStacheItemsToDisk::class);

beforeEach(function () {
    Entry::make()
        ->collection('events')
        ->slug('recurring-event')
        ->id('recurring-event')
        ->data([
            'title' => 'Recurring Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'weekly',
            'categories' => ['one'],
        ])->save();

    $this->tag = app(Events::class);
});

test('can offset upcoming occurrences', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'limit' => 5,
            'offset' => 2,
        ]);

    $occurrences = $this->tag->upcoming();

    expect($occurrences)->toHaveCount(3);
});

test('can offset between occurrences', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag->setContext([])
        ->setParameters([
            'collection' => 'events',
            'from' => Carbon::now()->toDateString(),
            'to' => Carbon::now()->addWeek(3),
            'offset' => 2,
        ]);

    $occurrences = $this->tag->between();

    expect($occurrences)->toHaveCount(2);
});

test('can offset today occurrences', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('12:01'));

    Entry::make()
        ->collection('events')
        ->slug('single-event')
        ->data([
            'title' => 'Single Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
        ])->save();

    $this->tag->setContext([])
        ->setParameters([
            'collection' => 'events',
            'offset' => 1,
        ]);

    expect($this->tag->today())->toHaveCount(1);

    $this->tag->setContext([])
        ->setParameters([
            'collection' => 'events',
            'ignore_finished' => true,
            'offset' => 1,
        ]);

    expect($this->tag->today())->toHaveCount(0);
});

test('can offset single day occurrences', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag->setContext([])
        ->setParameters([
            'collection' => 'events',
            'offset' => 1,
        ]);

    expect($this->tag->today())->toHaveCount(0);
});