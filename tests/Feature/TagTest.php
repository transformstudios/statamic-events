<?php

uses(\TransformStudios\Events\Tests\TestCase::class);
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Cascade;
use Statamic\Facades\Entry;
use Statamic\Support\Arr;
use TransformStudios\Events\Tags\Events;

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

test('can generate between occurrences', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'from' => Carbon::now(),
            'to' => Carbon::now()->addWeek(3),
        ]);

    $occurrences = $this->tag->between();

    expect($occurrences)->toHaveCount(4);
});

test('can generate between occurrences with default from', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'to' => Carbon::now()->addWeeks(3),
        ]);

    $occurrences = $this->tag->between();

    expect($occurrences)->toHaveCount(4);
});

test('can generate calendar occurrences', function () {
    Carbon::setTestNow('jan 1, 2022 10:00');

    Entry::all()->each->delete();

    Entry::make()
        ->collection('events')
        ->slug('single-event-start-of-month')
        ->data([
            'title' => 'Single Event - Start of Month',
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
        ])->save();

    Entry::make()
        ->collection('events')
        ->slug('recurring-event-start-of-month')
        ->data([
            'title' => 'Recurring Event - Start of Month',
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'weekly',
            'categories' => ['one'],
        ])->save();

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'month' => now()->englishMonth,
            'year' => now()->year,
        ]);

    $occurrences = $this->tag->calendar();

    expect($occurrences)->toHaveCount(42);
    expect(Arr::get($occurrences, '5.dates'))->toHaveCount(2);
    expect(Arr::get($occurrences, '6.no_results'))->toBeTrue();
});

test('can generate in occurrences', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'next' => '3 weeks',
        ]);

    $occurrences = $this->tag->in();

    expect($occurrences)->toHaveCount(4);
});

test('can generate today occurrences', function () {
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

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
        ]);

    expect($this->tag->today())->toHaveCount(2);

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'ignore_finished' => true,
        ]);

    expect($this->tag->today())->toHaveCount(1);
});

test('can generate upcoming occurrences', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'limit' => 3,
        ]);

    $occurrences = $this->tag->upcoming();

    expect($occurrences)->toHaveCount(3);
});

test('can generate upcoming limited occurrences', function () {
    Entry::make()
        ->collection('events')
        ->slug('another-recurring-event')
        ->id('another-recurring-event')
        ->data([
            'title' => 'Recurring Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'recurrence' => 'daily',
        ])->save();

    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'limit' => 3,
        ]);

    $occurrences = $this->tag->upcoming();

    expect($occurrences)->toHaveCount(3);
});

test('can paginate upcoming occurrences', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'paginate' => 2,
            'limit' => 10,
        ]);

    Cascade::partialMock()->shouldReceive('get')
        ->with('uri')
        ->andReturn('/events');

    $pagination = $this->tag->upcoming();

    expect($pagination)->toHaveKey('results');
    expect($pagination)->toHaveKey('paginate');
    expect($pagination)->toHaveKey('total_results');

    expect($pagination['results'])->toHaveCount(2);
    expect($pagination['total_results'])->toEqual(2);
    expect($pagination['paginate']['next_page'])->toEqual('/events?page=2');
});

test('can generate upcoming occurrences with taxonomy terms', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('single-event')
        ->id('single-event')
        ->data([
            'title' => 'Single Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '19:00',
        ])->save();

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'from' => Carbon::now()->toDateString(),
            'to' => Carbon::now()->addDay()->toDateString(),
            'taxonomy:categories' => 'one',
        ]);

    $occurrences = $this->tag->between();

    expect($occurrences)->toHaveCount(1);
});

test('can generate upcoming occurrences with filter', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    Entry::make()
        ->collection('events')
        ->slug('single-event')
        ->id('single-event')
        ->data([
            'title' => 'Single Event',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '19:00',
        ])->save();

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'from' => Carbon::now()->toDateString(),
            'to' => Carbon::now()->addDay()->toDateString(),
            'title:contains' => 'Single',
        ]);

    $occurrences = $this->tag->between();

    expect($occurrences)->toHaveCount(1);
});

test('can generate date event download link', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'date' => now(),
            'event' => 'recurring-event',
        ]);

    $url = $this->tag->downloadLink();

    expect($url)->toEqual('http://localhost/!/events/ics?collection=events&date='.now()->toDateString().'&event=recurring-event');
});

test('can generate event download link', function () {
    Carbon::setTestNow(now()->setTimeFromTimeString('10:00'));

    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'event' => 'recurring-event',
        ]);

    $url = $this->tag->downloadLink();

    expect($url)->toEqual('http://localhost/!/events/ics?collection=events&event=recurring-event');
});

test('can sort occurrences desc', function () {
    $this->tag
        ->setContext([])
        ->setParameters([
            'collection' => 'events',
            'limit' => 3,
            'sort' => 'desc',
        ]);

    $occurrences = $this->tag->upcoming();

    expect($occurrences[0]->start->isAfter($occurrences[1]->start))->toBeTrue();
    expect($occurrences[1]->start->isAfter($occurrences[2]->start))->toBeTrue();
});